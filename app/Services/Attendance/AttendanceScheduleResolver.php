<?php

namespace App\Services\Attendance;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceScheduleAssignment;
use App\Models\ClassSection;
use App\Models\InstitutionSetting;

class AttendanceScheduleResolver
{
    /** @var array<int, array{check_in_time: string, check_out_time: ?string, late_margin_minutes: int, source: string, schedule: ?AttendanceSchedule}> */
    private array $cache = [];

    /**
     * Resolve effective attendance timing for a class section.
     * Precedence: section assignment → grade assignment → institution settings.
     *
     * @return array{check_in_time: string, check_out_time: ?string, late_margin_minutes: int, source: string, schedule: ?AttendanceSchedule}
     */
    public function resolveForClassSection(int $institutionId, ClassSection|int $section): array
    {
        $sectionId = $section instanceof ClassSection ? (int) $section->id : (int) $section;

        if (isset($this->cache[$sectionId])) {
            return $this->cache[$sectionId];
        }

        $sectionModel = $section instanceof ClassSection
            ? $section
            : ClassSection::query()->find($sectionId);

        if ($sectionModel) {
            $sectionAssignment = AttendanceScheduleAssignment::query()
                ->with('schedule')
                ->where('institution_id', $institutionId)
                ->where('class_section_id', $sectionModel->id)
                ->first();

            if ($sectionAssignment?->schedule && $sectionAssignment->schedule->is_active) {
                return $this->cache[$sectionId] = $this->fromSchedule($sectionAssignment->schedule, 'section');
            }

            if ($sectionModel->grade_level_id) {
                $gradeAssignment = AttendanceScheduleAssignment::query()
                    ->with('schedule')
                    ->where('institution_id', $institutionId)
                    ->where('grade_level_id', $sectionModel->grade_level_id)
                    ->first();

                if ($gradeAssignment?->schedule && $gradeAssignment->schedule->is_active) {
                    return $this->cache[$sectionId] = $this->fromSchedule($gradeAssignment->schedule, 'grade');
                }
            }
        }

        return $this->cache[$sectionId] = $this->fromInstitution($institutionId);
    }

    /**
     * @return array{check_in_time: string, check_out_time: ?string, late_margin_minutes: int, source: string, schedule: ?AttendanceSchedule}
     */
    private function fromSchedule(AttendanceSchedule $schedule, string $source): array
    {
        return [
            'check_in_time' => $schedule->checkInTimeLabel(),
            'check_out_time' => $schedule->checkOutTimeLabel(),
            'late_margin_minutes' => (int) $schedule->late_margin_minutes,
            'source' => $source,
            'schedule' => $schedule,
        ];
    }

    /**
     * @return array{check_in_time: string, check_out_time: ?string, late_margin_minutes: int, source: string, schedule: ?AttendanceSchedule}
     */
    private function fromInstitution(int $institutionId): array
    {
        $checkIn = (string) InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $checkOut = InstitutionSetting::get($institutionId, 'school_end_time', null);
        $lateMargin = (int) InstitutionSetting::get($institutionId, 'late_margin_time', 0);

        return [
            'check_in_time' => $checkIn ?: '08:00',
            'check_out_time' => $checkOut ? (string) $checkOut : null,
            'late_margin_minutes' => $lateMargin,
            'source' => 'institution',
            'schedule' => null,
        ];
    }
}
