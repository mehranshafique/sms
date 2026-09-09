<?php

namespace App\Services\Attendance;

use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\Timetable;
use Carbon\Carbon;

class AttendanceGateStatusService
{
    public function __construct(
        private readonly AttendanceScheduleResolver $scheduleResolver
    ) {}

    /**
     * Determine present / late / absent for a student gate punch.
     *
     * @param  array{check_in_time: string, check_out_time: ?string, late_margin_minutes: int, source: string}|null  $resolved
     * @return array{status: string, subject_id: ?int, slot_label: ?string}
     */
    public function resolve(
        int $classSectionId,
        int $institutionId,
        Carbon $scanTime,
        ?array $resolved = null
    ): array {
        $resolved ??= $this->scheduleResolver->resolveForClassSection($institutionId, $classSectionId);
        $lateMarginMinutes = (int) ($resolved['late_margin_minutes'] ?? 0);
        $checkInTime = (string) ($resolved['check_in_time'] ?? '08:00');

        $isSubjectWise = $this->isSubjectWiseInstitution($institutionId);

        if ($isSubjectWise) {
            return $this->resolveSubjectWise(
                $classSectionId,
                $institutionId,
                $scanTime,
                $lateMarginMinutes,
                $checkInTime
            );
        }

        return $this->resolveAgainstCheckIn($scanTime, $checkInTime, $lateMarginMinutes);
    }

    /**
     * @return array{status: string, subject_id: ?int, slot_label: ?string}
     */
    private function resolveSubjectWise(
        int $classSectionId,
        int $institutionId,
        Carbon $scanTime,
        int $lateMarginMinutes,
        string $checkInTime
    ): array {
        $day = strtolower($scanTime->format('l'));
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        $slotsQuery = Timetable::with('subject')
            ->where('class_section_id', $classSectionId)
            ->whereRaw('LOWER(day_of_week) = ?', [$day])
            ->orderBy('start_time');

        if ($session) {
            $slotsQuery->where('academic_session_id', $session->id);
        }

        $slots = $slotsQuery->get();

        foreach ($slots as $slot) {
            $start = $this->combineDateAndTime($scanTime, $slot->start_time);
            $end = $this->combineDateAndTime($scanTime, $slot->end_time);
            if ($scanTime->between($start, $end)) {
                $lateThreshold = $start->copy()->addMinutes($lateMarginMinutes);

                return [
                    'status' => $scanTime->lte($lateThreshold) ? 'present' : 'late',
                    'subject_id' => $slot->subject_id,
                    'slot_label' => $slot->subject?->name,
                ];
            }
        }

        $lastEnded = null;
        foreach ($slots as $slot) {
            $end = $this->combineDateAndTime($scanTime, $slot->end_time);
            if ($scanTime->gt($end)) {
                $lastEnded = $slot;
            }
        }

        if ($lastEnded) {
            return [
                'status' => 'absent',
                'subject_id' => $lastEnded->subject_id,
                'slot_label' => $lastEnded->subject?->name,
            ];
        }

        return $this->resolveAgainstCheckIn($scanTime, $checkInTime, $lateMarginMinutes);
    }

    /**
     * @return array{status: string, subject_id: ?int, slot_label: ?string}
     */
    private function resolveAgainstCheckIn(Carbon $scanTime, string $checkInTime, int $lateMarginMinutes): array
    {
        try {
            $parsedStartTime = $this->parseTimeOfDay($checkInTime);
            $expectedTime = $scanTime->copy()->setTime($parsedStartTime['h'], $parsedStartTime['m'], 0);
            $expectedTime->addMinutes($lateMarginMinutes);
            $isLate = $scanTime->gt($expectedTime);
        } catch (\Exception $e) {
            $isLate = $scanTime->format('H:i') > '08:00';
        }

        return [
            'status' => $isLate ? 'late' : 'present',
            'subject_id' => null,
            'slot_label' => null,
        ];
    }

    public function isSubjectWiseInstitution(int $institutionId): bool
    {
        $institution = Institution::find($institutionId);
        if (! $institution) {
            return false;
        }
        $type = is_object($institution->type) ? $institution->type->value : $institution->type;

        return in_array($type, ['university', 'vocational', 'lmd'], true);
    }

    public function combineDateAndTime(Carbon $day, mixed $timeValue): Carbon
    {
        $parts = $this->parseTimeOfDay($timeValue);

        return $day->copy()->setTime($parts['h'], $parts['m'], $parts['s']);
    }

    /**
     * @return array{h:int,m:int,s:int}
     */
    public function parseTimeOfDay(mixed $timeValue): array
    {
        if ($timeValue instanceof Carbon) {
            return [
                'h' => (int) $timeValue->hour,
                'm' => (int) $timeValue->minute,
                's' => (int) $timeValue->second,
            ];
        }

        $raw = trim((string) $timeValue);
        if ($raw === '') {
            return ['h' => 8, 'm' => 0, 's' => 0];
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $m)) {
            return [
                'h' => (int) $m[1],
                'm' => (int) $m[2],
                's' => isset($m[3]) ? (int) $m[3] : 0,
            ];
        }

        $parsed = Carbon::parse($raw);

        return [
            'h' => (int) $parsed->hour,
            'm' => (int) $parsed->minute,
            's' => (int) $parsed->second,
        ];
    }
}
