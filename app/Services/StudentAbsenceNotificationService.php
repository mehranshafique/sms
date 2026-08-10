<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StudentAbsenceNotificationService
{
    public const EVENT_KEY = 'student_absent';

    public function __construct(protected NotificationService $notifications)
    {
    }

    /**
     * Notify parents for students newly marked absent (deduped per student/day).
     *
     * @param  list<int|string>  $studentIds
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function notifyForStudents(int $institutionId, array $studentIds, string|Carbon $date): array
    {
        $stats = ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        if (! $this->isAutoNotifyEnabled($institutionId)) {
            return $stats;
        }

        $dateStr = Carbon::parse($date)->toDateString();
        $uniqueIds = collect($studentIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        foreach ($uniqueIds as $studentId) {
            $result = $this->notifyStudent($institutionId, $studentId, $dateStr);
            $stats[$result]++;
        }

        return $stats;
    }

    /**
     * Notify all today's absentees who have not yet been notified (daily batch).
     *
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function notifyPendingForDate(?string $date = null): array
    {
        $dateStr = Carbon::parse($date ?? Carbon::today())->toDateString();
        $stats = ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        $groups = StudentAttendance::query()
            ->whereDate('attendance_date', $dateStr)
            ->where('status', 'absent')
            ->whereNull('parents_notified_at')
            ->select('institution_id', 'student_id')
            ->distinct()
            ->get()
            ->groupBy('institution_id');

        foreach ($groups as $institutionId => $rows) {
            if (! $this->isAutoNotifyEnabled((int) $institutionId)) {
                $stats['skipped'] += $rows->count();
                continue;
            }

            foreach ($rows as $row) {
                $result = $this->notifyStudent((int) $institutionId, (int) $row->student_id, $dateStr);
                $stats[$result]++;
            }
        }

        return $stats;
    }

    /**
     * @return 'sent'|'skipped'|'failed'
     */
    public function notifyStudent(int $institutionId, int $studentId, string $dateStr): string
    {
        if ($this->alreadyNotified($studentId, $dateStr)) {
            return 'skipped';
        }

        $stillAbsent = StudentAttendance::query()
            ->where('student_id', $studentId)
            ->whereDate('attendance_date', $dateStr)
            ->where('status', 'absent')
            ->exists();

        if (! $stillAbsent) {
            return 'skipped';
        }

        $student = Student::with(['parent', 'institution'])->find($studentId);
        if (! $student) {
            return 'failed';
        }

        $phone = $this->resolveParentPhone($student);
        if (! $phone) {
            Log::info('Absent notify skipped: no parent phone', [
                'student_id' => $studentId,
                'date' => $dateStr,
            ]);

            return 'skipped';
        }

        $sendSms = $this->notifications->channelEnabled($institutionId, self::EVENT_KEY, 'sms');
        $sendWa = $this->notifications->channelEnabled($institutionId, self::EVENT_KEY, 'whatsapp');

        if (! $sendSms && ! $sendWa) {
            return 'skipped';
        }

        $payload = [
            'StudentName' => $student->full_name,
            'ParentName' => $student->parent?->father_name
                ?? $student->parent?->mother_name
                ?? $student->parent?->guardian_name
                ?? 'Parent',
            'Date' => Carbon::parse($dateStr)->format('d/m/Y'),
            'SchoolName' => $student->institution?->name ?? 'School',
        ];

        $anySent = false;

        if ($sendSms) {
            $response = $this->notifications->sendNotificationEvent(
                self::EVENT_KEY,
                $phone,
                $payload,
                $institutionId,
                'sms'
            );
            $anySent = $anySent || ($response['success'] ?? false);
        }

        if ($sendWa) {
            $response = $this->notifications->sendNotificationEvent(
                self::EVENT_KEY,
                $phone,
                $payload,
                $institutionId,
                'whatsapp'
            );
            $anySent = $anySent || ($response['success'] ?? false);
        }

        if ($anySent) {
            $this->markNotified($studentId, $dateStr);

            return 'sent';
        }

        return 'failed';
    }

    public function isAutoNotifyEnabled(int $institutionId): bool
    {
        return (bool) InstitutionSetting::get($institutionId, 'auto_notify_absent', 1);
    }

    public function alreadyNotified(int $studentId, string $dateStr): bool
    {
        return StudentAttendance::query()
            ->where('student_id', $studentId)
            ->whereDate('attendance_date', $dateStr)
            ->whereNotNull('parents_notified_at')
            ->exists();
    }

    protected function markNotified(int $studentId, string $dateStr): void
    {
        StudentAttendance::query()
            ->where('student_id', $studentId)
            ->whereDate('attendance_date', $dateStr)
            ->where('status', 'absent')
            ->update(['parents_notified_at' => now()]);
    }

    protected function resolveParentPhone(Student $student): ?string
    {
        $parent = $student->parent;
        if ($parent) {
            $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
            $phone = $parent->{$phoneField}
                ?? $parent->father_phone
                ?? $parent->mother_phone
                ?? $parent->guardian_phone
                ?? null;
            if (! empty($phone)) {
                return $phone;
            }
        }

        return $student->mobile_number ?? $student->phone ?? null;
    }

    /**
     * Collect student IDs marked absent from a bulk attendance map.
     *
     * @param  array<int|string, string|array>  $attendanceMap
     * @return Collection<int, int>
     */
    public function absentStudentIdsFromMap(array $attendanceMap): Collection
    {
        return collect($attendanceMap)
            ->filter(function ($status) {
                if (is_array($status)) {
                    $status = $status['status'] ?? null;
                }

                return $status === 'absent';
            })
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
