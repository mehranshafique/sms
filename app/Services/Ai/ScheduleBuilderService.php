<?php

namespace App\Services\Ai;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamClassSubjectSetting;
use App\Models\InstitutionSetting;
use App\Models\Subject;
use App\Models\Timetable;
use Carbon\Carbon;

/**
 * Deterministic schedule builders for exam date sheets and class timetables.
 * Respects school hours, exam windows, room limits, and existing conflicts.
 */
class ScheduleBuilderService
{
    /** @return array{schedule: array<int, array{date: string, start_time: string, end_time: string, room_number: string}>, meta: array} */
    public function buildExamDatesheet(int $examId, int $classSectionId, ?int $institutionId, array $options = []): array
    {
        $exam = Exam::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))->findOrFail($examId);
        $classSection = ClassSection::findOrFail($classSectionId);

        if ($institutionId && (int) $classSection->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $institutionId = $institutionId ?: (int) $classSection->institution_id;

        $schoolStart = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $schoolEnd   = InstitutionSetting::get($institutionId, 'school_end_time', '14:00');
        $roomsCount  = max(1, (int) InstitutionSetting::get($institutionId, 'school_rooms_count', 10));

        $durationMinutes = (int) ($options['duration_minutes'] ?? 120);
        $gapMinutes      = (int) ($options['gap_minutes'] ?? 30);
        $periodDays      = isset($options['period_days']) ? max(1, (int) $options['period_days']) : null;

        $subjects = $this->subjectsForClass($classSection, $exam->academic_session_id, $examId);

        $examStartDate = Carbon::parse($exam->start_date)->startOfDay();
        $examEndDate   = Carbon::parse($exam->end_date)->startOfDay();
        $today         = Carbon::now()->startOfDay();

        $currentDate = $examStartDate->lte($today) ? $today->copy()->addDay() : $examStartDate->copy();
        if ($currentDate->gt($examEndDate)) {
            $currentDate = $examEndDate->copy();
        }

        if ($periodDays !== null) {
            $windowEnd = $currentDate->copy()->addDays($periodDays - 1);
            if ($windowEnd->lt($examEndDate)) {
                $examEndDate = $windowEnd;
            }
        }

        $schedule   = [];
        $roomIndex  = 0;
        $currentStartTime = Carbon::parse($schoolStart);
        $maxEndTime       = Carbon::parse($schoolEnd);

        foreach ($subjects as $subject) {
            while ($currentDate->isSunday()) {
                $currentDate->addDay();
                $currentStartTime = Carbon::parse($schoolStart);
            }

            if ($currentDate->gt($examEndDate)) {
                $currentDate = $examEndDate->copy();
            }

            $proposedEndTime = $currentStartTime->copy()->addMinutes($durationMinutes);

            if ($proposedEndTime->format('H:i') > $maxEndTime->format('H:i')) {
                $currentDate->addDay();
                while ($currentDate->isSunday()) {
                    $currentDate->addDay();
                }
                if ($currentDate->gt($examEndDate)) {
                    $currentDate = $examEndDate->copy();
                }
                $currentStartTime = Carbon::parse($schoolStart);
                $proposedEndTime  = $currentStartTime->copy()->addMinutes($durationMinutes);
            }

            $roomIndex++;
            $roomNumber = 'Room ' . ((($roomIndex - 1) % $roomsCount) + 1);

            $schedule[$subject->id] = [
                'date'         => $currentDate->format('Y-m-d'),
                'start_time'   => $currentStartTime->format('H:i'),
                'end_time'     => $proposedEndTime->format('H:i'),
                'room_number'  => $roomNumber,
                'subject_name' => $subject->name,
            ];

            $currentStartTime = $proposedEndTime->copy()->addMinutes($gapMinutes);
        }

        return [
            'schedule' => $schedule,
            'meta'     => [
                'exam'         => $exam->name,
                'class'        => $classSection->name,
                'exam_start'   => $exam->start_date->format('Y-m-d'),
                'exam_end'     => $exam->end_date->format('Y-m-d'),
                'school_start' => $schoolStart,
                'school_end'   => $schoolEnd,
                'rooms_count'  => $roomsCount,
                'subject_count'=> count($schedule),
            ],
        ];
    }

    /** @return array{slots: array<int, array>, meta: array, conflicts_avoided: int} */
    public function buildClassTimetable(int $classSectionId, ?int $institutionId, array $options = []): array
    {
        $classSection = ClassSection::with('gradeLevel')->findOrFail($classSectionId);
        $institutionId = $institutionId ?: (int) $classSection->institution_id;

        if ($institutionId && (int) $classSection->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $session = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first();

        if (!$session) {
            return ['slots' => [], 'meta' => ['error' => 'no_session'], 'conflicts_avoided' => 0];
        }

        $schoolStart    = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $schoolEnd      = InstitutionSetting::get($institutionId, 'school_end_time', '15:00');
        $roomsCount     = max(1, (int) InstitutionSetting::get($institutionId, 'school_rooms_count', 10));
        $periodMinutes  = (int) ($options['period_minutes'] ?? 45);
        $gapMinutes     = (int) ($options['gap_minutes'] ?? 10);
        $days           = $options['days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $ignoreSameClass = (bool) ($options['ignore_same_class'] ?? true);

        $allocations = ClassSubject::where('class_section_id', $classSectionId)
            ->where('academic_session_id', $session->id)
            ->with(['subject', 'teacher.user'])
            ->get();

        if ($allocations->isEmpty()) {
            $allocations = ClassSubject::where('class_section_id', $classSectionId)
                ->with(['subject', 'teacher.user'])
                ->get();
        }

        if ($allocations->isEmpty()) {
            $subjects = Subject::where('grade_level_id', $classSection->grade_level_id)
                ->where('is_active', true)
                ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
                ->get();

            $allocations = $subjects->map(fn ($s) => (object) [
                'subject_id'  => $s->id,
                'subject'     => $s,
                'teacher_id'  => null,
                'teacher'     => null,
            ]);
        }

        $existing = Timetable::where('institution_id', $institutionId)
            ->where('academic_session_id', $session->id)
            ->get();

        $existingClassCount = $existing->where('class_section_id', $classSectionId)->count();

        $timeSlots = $this->buildTimeSlots($schoolStart, $schoolEnd, $periodMinutes, $gapMinutes);
        $slots     = [];
        $roomIdx   = 0;
        $conflicts = 0;

        foreach ($allocations as $idx => $alloc) {
            $subjectId  = $alloc->subject_id ?? $alloc->subject->id;
            $teacherId  = $alloc->teacher_id ?? null;
            $placed     = false;

            foreach ($days as $dayOffset => $day) {
                if ($placed) {
                    break;
                }
                foreach ($timeSlots as $slot) {
                    $roomIdx++;
                    $room = 'Room ' . ((($roomIdx - 1) % $roomsCount) + 1);

                    if ($this->hasConflict($existing, $slots, $day, $slot['start'], $slot['end'], $classSectionId, $teacherId, $room, $ignoreSameClass)) {
                        $conflicts++;
                        continue;
                    }

                    $slots[] = [
                        'class_section_id' => $classSectionId,
                        'subject_id'       => $subjectId,
                        'subject_name'     => $alloc->subject->name ?? 'Subject',
                        'teacher_id'       => $teacherId,
                        'teacher_name'     => $alloc->teacher->user->name ?? null,
                        'day_of_week'      => $day,
                        'start_time'       => $slot['start'],
                        'end_time'         => $slot['end'],
                        'room_number'      => $room,
                    ];
                    $placed = true;
                    break;
                }
            }
        }

        return [
            'slots' => $slots,
            'meta'  => [
                'class'                => trim(($classSection->gradeLevel->name ?? '') . ' ' . $classSection->name),
                'class_section_id'     => $classSectionId,
                'school_start'         => $schoolStart,
                'school_end'           => $schoolEnd,
                'rooms_count'          => $roomsCount,
                'session_id'           => $session->id,
                'existing_class_slots' => $existingClassCount,
                'subject_count'        => $allocations->count(),
            ],
            'conflicts_avoided' => $conflicts,
        ];
    }

    /** @return \Illuminate\Support\Collection<int, Subject> */
    protected function subjectsForClass(ClassSection $classSection, int $sessionId, ?int $examId = null)
    {
        $allocatedIds = ClassSubject::where('class_section_id', $classSection->id)
            ->where('academic_session_id', $sessionId)
            ->pluck('subject_id');

        if ($allocatedIds->isNotEmpty()) {
            $subjects = Subject::whereIn('id', $allocatedIds)->where('is_active', true)->get();
        } else {
            $subjects = Subject::where('grade_level_id', $classSection->grade_level_id)
                ->where('is_active', true)
                ->get();
        }

        if ($examId) {
            $excluded = ExamClassSubjectSetting::where('exam_id', $examId)
                ->where('class_section_id', $classSection->id)
                ->where('is_examined', false)
                ->pluck('subject_id');
            $subjects = $subjects->whereNotIn('id', $excluded);
        }

        return $subjects->values();
    }

    /** @return array<int, array{start: string, end: string}> */
    protected function buildTimeSlots(string $schoolStart, string $schoolEnd, int $periodMinutes, int $gapMinutes): array
    {
        $slots   = [];
        $current = Carbon::parse($schoolStart);
        $end     = Carbon::parse($schoolEnd);

        while (true) {
            $slotEnd = $current->copy()->addMinutes($periodMinutes);
            if ($slotEnd->format('H:i') > $end->format('H:i')) {
                break;
            }
            $slots[] = [
                'start' => $current->format('H:i'),
                'end'   => $slotEnd->format('H:i'),
            ];
            $current = $slotEnd->copy()->addMinutes($gapMinutes);
        }

        return $slots;
    }

    protected function hasConflict($existing, array $proposed, string $day, string $start, string $end, int $classId, ?int $teacherId, string $room, bool $ignoreSameClass = false): bool
    {
        $all = $existing->concat(collect($proposed)->map(fn ($s) => (object) $s));

        foreach ($all as $slot) {
            $slotDay = is_object($slot) ? ($slot->day_of_week ?? null) : ($slot['day_of_week'] ?? null);
            if (strtolower((string) $slotDay) !== strtolower($day)) {
                continue;
            }

            $slotStart = $this->timeStr($slot->start_time ?? $slot['start_time'] ?? null);
            $slotEnd   = $this->timeStr($slot->end_time ?? $slot['end_time'] ?? null);

            if (!$this->timesOverlap($start, $end, $slotStart, $slotEnd)) {
                continue;
            }

            $slotClass   = $slot->class_section_id ?? $slot['class_section_id'] ?? null;
            $slotTeacher = $slot->teacher_id ?? $slot['teacher_id'] ?? null;
            $slotRoom    = $slot->room_number ?? $slot['room_number'] ?? null;

            if ((int) $slotClass === $classId) {
                if ($ignoreSameClass) {
                    continue;
                }
                return true;
            }
            if ($teacherId && $slotTeacher && (int) $slotTeacher === (int) $teacherId) {
                return true;
            }
            if ($slotRoom && $room && strcasecmp((string) $slotRoom, $room) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function timeStr($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }
        return substr((string) $value, 0, 5);
    }

    protected function timesOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart < $bEnd && $bStart < $aEnd;
    }
}
