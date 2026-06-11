<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\AttendanceReportDelivery;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceReportDispatchService
{
    public function __construct(
        protected AttendanceAnalyticsService $analytics,
        protected NotificationService $notifications
    ) {}

    public function dispatchForInstitution(int $institutionId, string $periodType = 'week'): array
    {
        $settingKey = $periodType === 'month' ? 'attendance_monthly_report_enabled' : 'attendance_weekly_report_enabled';
        if (!InstitutionSetting::get($institutionId, $settingKey, '1')) {
            return ['sent' => 0, 'skipped' => 0, 'message' => 'Disabled for institution'];
        }

        $minDays = (int) InstitutionSetting::get($institutionId, 'attendance_report_min_days', 1);
        $channel = InstitutionSetting::get($institutionId, 'attendance_report_channel', 'sms');
        $eventKey = $periodType === 'month' ? 'attendance_monthly_summary' : 'attendance_weekly_summary';

        $institution = Institution::find($institutionId);
        if (!$institution) {
            return ['sent' => 0, 'skipped' => 0];
        }

        $isSubjectWise = in_array(
            $institution->type instanceof \App\Enums\InstitutionType
                ? $institution->type->value
                : (string) $institution->type,
            ['university', 'vocational'],
            true
        );

        $now = Carbon::now();
        [$start, $end] = $periodType === 'month'
            ? [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]
            : [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];

        $sent = 0;
        $skipped = 0;

        $students = Student::with('parent')
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->get();

        foreach ($students as $student) {
            if ($this->alreadySent($student->id, $periodType, $start)) {
                $skipped++;
                continue;
            }

            $enrollment = StudentEnrollment::where('student_id', $student->id)
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$enrollment) {
                $skipped++;
                continue;
            }

            $summary = $this->analytics->getComparativeSummaryTable(
                $student->id,
                $enrollment->class_section_id,
                $periodType,
                $isSubjectWise
            );

            if (($summary['current']['total_school_days'] ?? 0) < $minDays) {
                $skipped++;
                continue;
            }

            $phone = $student->parent?->guardian_phone
                ?? $student->parent?->father_phone
                ?? $student->parent?->mother_phone;

            if (!$phone) {
                $skipped++;
                continue;
            }

            $data = [
                'StudentName' => $student->full_name,
                'SchoolName' => $institution->name,
                'PeriodLabel' => $summary['period_label'],
                'TotalDays' => $summary['current']['total_school_days'],
                'Present' => $summary['current']['days_present'],
                'Absent' => $summary['current']['days_absent'],
                'Late' => $summary['current']['days_late'],
                'Percentage' => $summary['current']['attendance_percentage'] . '%',
                'PrevPercentage' => $summary['previous']['attendance_percentage'] . '%',
                'student_name' => $student->full_name,
                'school_name' => $institution->name,
            ];

            try {
                $this->notifications->sendNotificationEvent($eventKey, $phone, $data, $institutionId, $channel);
                AttendanceReportDelivery::create([
                    'institution_id' => $institutionId,
                    'student_id' => $student->id,
                    'period_type' => $periodType,
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'channel' => $channel,
                    'sent_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Attendance report send failed: ' . $e->getMessage(), ['student_id' => $student->id]);
                $skipped++;
            }
        }

        return compact('sent', 'skipped');
    }

    public function dispatchAll(string $periodType = 'week'): array
    {
        $totals = ['sent' => 0, 'skipped' => 0];
        Institution::where('is_active', true)->pluck('id')->each(function ($id) use ($periodType, &$totals) {
            $result = $this->dispatchForInstitution($id, $periodType);
            $totals['sent'] += $result['sent'];
            $totals['skipped'] += $result['skipped'];
        });
        return $totals;
    }

    private function alreadySent(int $studentId, string $periodType, Carbon $start): bool
    {
        return AttendanceReportDelivery::where('student_id', $studentId)
            ->where('period_type', $periodType)
            ->where('period_start', $start->toDateString())
            ->exists();
    }
}
