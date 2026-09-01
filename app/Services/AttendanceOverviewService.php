<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceOverviewService
{
    /**
     * Full management overview for a school on a given date.
     *
     * @return array{
     *   date: string,
     *   session_id: ?int,
     *   students: array,
     *   staff: array,
     *   classes: list<array>
     * }
     */
    public function forInstitution(int $institutionId, ?Carbon $date = null): array
    {
        $date = ($date ?? Carbon::today())->copy()->startOfDay();
        $sessionId = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');

        $enrollments = $this->activeEnrollments($institutionId, $sessionId);
        $studentIds = $enrollments->pluck('student_id')->unique()->values();
        $studentAttendance = $this->studentAttendanceByStudent($institutionId, $date, $studentIds);

        $students = $this->buildPeopleSummary(
            expectedIds: $studentIds,
            attendanceByPerson: $studentAttendance
        );

        $classes = $this->buildClassBreakdown($enrollments, $studentAttendance);

        $staffMembers = Staff::with('user')
            ->where('institution_id', $institutionId)
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->get();

        $staffIds = $staffMembers->pluck('id')->values();
        $staffAttendance = $this->staffAttendanceByStaff($institutionId, $date, $staffIds);

        $staff = $this->buildPeopleSummary(
            expectedIds: $staffIds,
            attendanceByPerson: $staffAttendance
        );

        return [
            'date' => $date->toDateString(),
            'session_id' => $sessionId ? (int) $sessionId : null,
            'students' => $students,
            'staff' => $staff,
            'classes' => $classes,
            'total_enrollment' => $students['expected'],
        ];
    }

    /**
     * Compact payload for the management home dashboard.
     */
    public function dashboardWidgets(int $institutionId, ?Carbon $date = null): array
    {
        $overview = $this->forInstitution($institutionId, $date);

        return [
            'date' => $overview['date'],
            'students' => $overview['students'],
            'staff' => $overview['staff'],
            'classes' => $overview['classes'],
            'total_enrollment' => $overview['total_enrollment'],
        ];
    }

    /**
     * Detail rows for a KPI drill-down.
     *
     * @param  'students'|'staff'  $audience
     * @param  'expected'|'present'|'absent'|'late'|'not_checked_in'  $bucket
     * @return list<array>
     */
    public function details(
        int $institutionId,
        string $audience,
        string $bucket,
        ?Carbon $date = null,
        ?int $classSectionId = null
    ): array {
        $date = ($date ?? Carbon::today())->copy()->startOfDay();
        $allowed = ['expected', 'present', 'absent', 'late', 'not_checked_in'];
        if (! in_array($bucket, $allowed, true)) {
            $bucket = 'expected';
        }

        if ($audience === 'staff') {
            return $this->staffDetails($institutionId, $bucket, $date);
        }

        return $this->studentDetails($institutionId, $bucket, $date, $classSectionId);
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    protected function activeEnrollments(int $institutionId, ?int $sessionId): Collection
    {
        $query = StudentEnrollment::with(['student', 'classSection.gradeLevel'])
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->whereNotNull('class_section_id');

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        return $query->get();
    }

    /**
     * Map student_id => resolved daily status + sample attendance row data.
     *
     * @param  Collection<int, int>  $studentIds
     * @return Collection<int, array{status: string, check_in: mixed, method: ?string, class_section_id: ?int}>
     */
    protected function studentAttendanceByStudent(int $institutionId, Carbon $date, Collection $studentIds): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        $records = StudentAttendance::query()
            ->where('institution_id', $institutionId)
            ->whereDate('attendance_date', $date->toDateString())
            ->whereIn('student_id', $studentIds->all())
            ->orderByRaw('CASE WHEN subject_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get()
            ->groupBy('student_id');

        return $records->map(function (Collection $rows) {
            $status = $this->resolvePersonStatus($rows->pluck('status'));
            $primary = $rows->firstWhere('subject_id', null) ?? $rows->first();

            return [
                'status' => $status,
                'check_in' => $primary?->check_in,
                'method' => $primary?->method,
                'class_section_id' => $primary?->class_section_id,
            ];
        });
    }

    /**
     * @param  Collection<int, int>  $staffIds
     * @return Collection<int, array{status: string, check_in: mixed, method: ?string}>
     */
    protected function staffAttendanceByStaff(int $institutionId, Carbon $date, Collection $staffIds): Collection
    {
        if ($staffIds->isEmpty()) {
            return collect();
        }

        $records = StaffAttendance::query()
            ->where('institution_id', $institutionId)
            ->whereDate('attendance_date', $date->toDateString())
            ->whereIn('staff_id', $staffIds->all())
            ->orderBy('id')
            ->get()
            ->groupBy('staff_id');

        return $records->map(function (Collection $rows) {
            return [
                'status' => $this->resolvePersonStatus($rows->pluck('status')),
                'check_in' => $rows->first()?->check_in,
                'method' => $rows->first()?->method,
            ];
        });
    }

    /**
     * Prefer the most favorable status when a person has multiple records for the day.
     *
     * @param  Collection<int, string>|iterable  $statuses
     */
    protected function resolvePersonStatus(iterable $statuses): string
    {
        $list = collect($statuses)->filter()->unique()->values()->all();
        foreach (['present', 'half_day', 'late', 'excused', 'absent'] as $preferred) {
            if (in_array($preferred, $list, true)) {
                return $preferred;
            }
        }

        return (string) ($list[0] ?? 'absent');
    }

    /**
     * @param  Collection<int, int>  $expectedIds
     * @param  Collection<int, array{status: string}>  $attendanceByPerson
     * @return array{expected:int,present:int,absent:int,late:int,not_checked_in:int,marked:int,rate:int}
     */
    protected function buildPeopleSummary(Collection $expectedIds, Collection $attendanceByPerson): array
    {
        $expected = $expectedIds->count();
        $present = 0;
        $absent = 0;
        $late = 0;
        $notCheckedIn = 0;

        foreach ($expectedIds as $id) {
            $row = $attendanceByPerson->get($id);
            if (! $row) {
                $notCheckedIn++;
                continue;
            }
            $bucket = $this->statusBucket($row['status']);
            if ($bucket === 'present') {
                $present++;
            } elseif ($bucket === 'late') {
                $late++;
            } else {
                $absent++;
            }
        }

        $marked = $present + $absent + $late;
        $rate = $expected > 0
            ? (int) round((($present + $late) / $expected) * 100)
            : 0;

        return [
            'expected' => $expected,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'not_checked_in' => $notCheckedIn,
            'marked' => $marked,
            'rate' => $rate,
        ];
    }

    protected function statusBucket(string $status): string
    {
        return match ($status) {
            'present', 'half_day' => 'present',
            'late' => 'late',
            default => 'absent', // absent, excused, unknown
        };
    }

    /**
     * @param  Collection<int, StudentEnrollment>  $enrollments
     * @param  Collection<int, array{status: string}>  $studentAttendance
     * @return list<array>
     */
    protected function buildClassBreakdown(Collection $enrollments, Collection $studentAttendance): array
    {
        $byClass = $enrollments->groupBy('class_section_id');
        $rows = [];

        foreach ($byClass as $classSectionId => $classEnrollments) {
            $section = $classEnrollments->first()?->classSection;
            $ids = $classEnrollments->pluck('student_id')->unique()->values();
            $summary = $this->buildPeopleSummary($ids, $studentAttendance->only($ids->all()));

            $rows[] = [
                'class_section_id' => (int) $classSectionId,
                'label' => class_section_label($section, 'grade_dash_section'),
                'grade' => $section?->gradeLevel?->name ?? '',
                'section' => $section?->name ?? '',
                'enrollment' => $summary['expected'],
                'present' => $summary['present'],
                'late' => $summary['late'],
                'absent' => $summary['absent'],
                'not_checked_in' => $summary['not_checked_in'],
                'rate' => $summary['rate'],
            ];
        }

        usort($rows, function ($a, $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });

        return $rows;
    }

    /**
     * @return list<array>
     */
    protected function studentDetails(
        int $institutionId,
        string $bucket,
        Carbon $date,
        ?int $classSectionId = null
    ): array {
        $sessionId = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');

        $enrollments = $this->activeEnrollments($institutionId, $sessionId ? (int) $sessionId : null);
        if ($classSectionId) {
            $enrollments = $enrollments->where('class_section_id', $classSectionId)->values();
        }

        $studentIds = $enrollments->pluck('student_id')->unique()->values();
        $attendance = $this->studentAttendanceByStudent($institutionId, $date, $studentIds);
        $enrollmentByStudent = $enrollments->keyBy('student_id');

        $rows = [];
        foreach ($studentIds as $studentId) {
            $att = $attendance->get($studentId);
            $exactStatus = $att['status'] ?? null;
            $rowBucket = $att ? $this->statusBucket($exactStatus) : 'not_checked_in';

            if ($bucket !== 'expected') {
                if ($bucket === 'not_checked_in' && $att) {
                    continue;
                }
                if ($bucket !== 'not_checked_in' && $rowBucket !== $bucket) {
                    continue;
                }
            }

            $enrollment = $enrollmentByStudent->get($studentId);
            $student = $enrollment?->student;
            $section = $enrollment?->classSection;

            $rows[] = [
                'id' => (int) $studentId,
                'name' => $student?->full_name ?? ('#'.$studentId),
                'secondary' => $student?->admission_number,
                'meta' => class_section_label($section, 'grade_dash_section'),
                'status' => $exactStatus ?? 'not_checked_in',
                'status_label' => $exactStatus
                    ? __('attendance.'.$exactStatus)
                    : __('attendance.not_checked_in'),
                'check_in' => $att['check_in'] ?? null,
                'check_in_label' => ! empty($att['check_in'])
                    ? Carbon::parse($att['check_in'])->format('H:i')
                    : '—',
                'url' => $studentId ? route('students.show', $studentId) : null,
            ];
        }

        usort($rows, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @return list<array>
     */
    protected function staffDetails(int $institutionId, string $bucket, Carbon $date): array
    {
        $staffMembers = Staff::with('user')
            ->where('institution_id', $institutionId)
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->get();

        $staffIds = $staffMembers->pluck('id')->values();
        $attendance = $this->staffAttendanceByStaff($institutionId, $date, $staffIds);
        $byId = $staffMembers->keyBy('id');

        $rows = [];
        foreach ($staffIds as $staffId) {
            $att = $attendance->get($staffId);
            $exactStatus = $att['status'] ?? null;
            $rowBucket = $att ? $this->statusBucket($exactStatus) : 'not_checked_in';

            if ($bucket !== 'expected') {
                if ($bucket === 'not_checked_in' && $att) {
                    continue;
                }
                if ($bucket !== 'not_checked_in' && $rowBucket !== $bucket) {
                    continue;
                }
            }

            $staff = $byId->get($staffId);
            $name = $staff?->user?->name ?? ('#'.$staffId);

            $rows[] = [
                'id' => (int) $staffId,
                'name' => $name,
                'secondary' => $staff?->employee_id,
                'meta' => $staff?->designation ?: __('attendance.staff'),
                'status' => $exactStatus ?? 'not_checked_in',
                'status_label' => $exactStatus
                    ? __('attendance.'.$exactStatus)
                    : __('attendance.not_checked_in'),
                'check_in' => $att['check_in'] ?? null,
                'check_in_label' => ! empty($att['check_in'])
                    ? Carbon::parse($att['check_in'])->format('H:i')
                    : '—',
                'url' => $staffId ? route('staff.show', $staffId) : null,
            ];
        }

        usort($rows, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $rows;
    }
}
