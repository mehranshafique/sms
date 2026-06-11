<?php

namespace App\Services;

use App\Models\StudentAttendance;
use Carbon\Carbon;

class AttendanceAnalyticsService
{
    /**
     * Generate comparative statistics for a given period
     * @param int $studentId
     * @param int $classSectionId
     * @param string $period 'week', 'month', 'quarter', 'semester', 'year'
     * @param bool $isSubjectWise
     * @param int|null $subjectId
     */
    public function getComparativeStats($studentId, $classSectionId, $period = 'week', $isSubjectWise = false, $subjectId = null)
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'week':
                $currentStart = $now->copy()->startOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'month':
                $currentStart = $now->copy()->startOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'quarter':
                $currentStart = $now->copy()->startOfQuarter();
                $previousStart = $now->copy()->subQuarter()->startOfQuarter();
                $previousEnd = $now->copy()->subQuarter()->endOfQuarter();
                break;
            case 'semester': // Assuming 6 months
                $currentStart = $now->copy()->subMonths(6);
                $previousStart = $now->copy()->subMonths(12);
                $previousEnd = $now->copy()->subMonths(6)->subDay();
                break;
            case 'year':
                $currentStart = $now->copy()->startOfYear();
                $previousStart = $now->copy()->subYear()->startOfYear();
                $previousEnd = $now->copy()->subYear()->endOfYear();
                break;
            default:
                $currentStart = $now->copy()->startOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
        }

        // Query Builder Helper for Student Records
        $buildRecordQuery = function($start, $end) use ($studentId, $isSubjectWise, $subjectId) {
            $query = StudentAttendance::where('student_id', $studentId)
                ->whereBetween('attendance_date', [$start, $end]);
            
            if ($isSubjectWise) {
                $query->where('subject_id', $subjectId);
            } else {
                $query->whereNull('subject_id');
            }
            return $query;
        };

        $currentRecords = $buildRecordQuery($currentStart, $now)->get();
        $previousRecords = $buildRecordQuery($previousStart, $previousEnd)->get();

        // Calculate actual Expected Days/Lectures dynamically by checking distinct dates marked for the whole class
        $getExpectedCount = function($start, $end) use ($classSectionId, $isSubjectWise, $subjectId) {
            $query = StudentAttendance::where('class_section_id', $classSectionId)
                ->whereBetween('attendance_date', [$start, $end]);
            
            if ($isSubjectWise) {
                $query->where('subject_id', $subjectId);
            } else {
                $query->whereNull('subject_id');
            }
            
            return $query->distinct()->count('attendance_date');
        };

        $currentExpectedDays = $getExpectedCount($currentStart, $now);

        $currentAvgMins = $this->calculateAverageArrivalMinutes($currentRecords);
        $previousAvgMins = $this->calculateAverageArrivalMinutes($previousRecords);

        $currentPunctuality = $this->calculatePunctualityScore($currentRecords);
        $previousPunctuality = $this->calculatePunctualityScore($previousRecords);

        return [
            'current_avg_time' => $this->formatMinutesToTime($currentAvgMins),
            'previous_avg_time' => $this->formatMinutesToTime($previousAvgMins),
            'arrival_insight' => $this->generateArrivalInsight($currentAvgMins, $previousAvgMins, $period),
            
            'current_punctuality' => $currentPunctuality,
            'previous_punctuality' => $previousPunctuality,
            'punctuality_insight' => $this->generatePunctualityInsight($currentPunctuality, $previousPunctuality, $period),
            
            'participation_rate' => $this->calculateParticipationRate($currentRecords, $currentExpectedDays),
            'records' => $currentRecords
        ];
    }

    private function calculateAverageArrivalMinutes($records)
    {
        $totalMinutes = 0;
        $count = 0;

        foreach ($records as $record) {
            if ($record->check_in) {
                $time = Carbon::parse($record->check_in);
                $totalMinutes += ($time->hour * 60) + $time->minute;
                $count++;
            }
        }
        return $count > 0 ? ($totalMinutes / $count) : 0;
    }

    private function formatMinutesToTime($minutes)
    {
        if ($minutes == 0) return 'N/A';
        $h = floor($minutes / 60);
        $m = round($minutes % 60);
        return sprintf('%02d:%02d AM', $h, $m);
    }

    private function calculatePunctualityScore($records)
    {
        if ($records->isEmpty()) return 0;
        $total = $records->count();
        $onTime = $records->whereIn('status', ['present', 'excused'])->count();
        return round(($onTime / $total) * 100);
    }

    private function calculateParticipationRate($records, $expectedDays)
    {
        if ($expectedDays <= 0) return 0;
        $attended = $records->whereIn('status', ['present', 'late', 'excused', 'half_day'])->count();
        return round(min(($attended / $expectedDays) * 100, 100));
    }

    private function generateArrivalInsight($current, $previous, $period)
    {
        if ($current == 0 || $previous == 0) return "Not enough data for comparison.";
        
        $diff = $previous - $current; // Positive means they arrived earlier
        $periodTxt = $period == 'week' ? 'last week' : 'last ' . $period;

        if ($diff > 5) {
            return "🔥 Arriving an average of " . round($diff) . " minutes earlier than $periodTxt.";
        } elseif ($diff < -5) {
            return "⚠️ Arriving an average of " . round(abs($diff)) . " minutes later than $periodTxt.";
        } else {
            return "👍 Consistent arrival times compared to $periodTxt.";
        }
    }

    private function generatePunctualityInsight($current, $previous, $period)
    {
        if ($current == 0 && $previous == 0) return "No punctuality data available.";
        
        $diff = $current - $previous;
        $periodTxt = $period == 'week' ? 'last week' : 'last ' . $period;

        if ($diff > 0) {
            return "📈 Punctuality increased by {$diff}% compared to $periodTxt.";
        } elseif ($diff < 0) {
            return "📉 Punctuality dropped by " . abs($diff) . "% compared to $periodTxt.";
        } else {
            return "Punctuality is identical to $periodTxt.";
        }
    }

    /**
     * Parent-friendly period summary: school days, present, absent, late, percentage.
     */
    public function getPeriodSummary(
        int $studentId,
        ?int $classSectionId,
        Carbon $start,
        Carbon $end,
        bool $isSubjectWise = false,
        ?int $subjectId = null
    ): array {
        $records = $this->fetchRecords($studentId, $start, $end, $isSubjectWise, $subjectId);
        $expectedDays = $this->countExpectedDays($classSectionId, $start, $end, $isSubjectWise, $subjectId);

        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $late = $records->where('status', 'late')->count();
        $excused = $records->whereIn('status', ['excused', 'half_day'])->count();
        $attended = $present + $late + $excused;

        $percentage = $expectedDays > 0 ? round(min(($attended / $expectedDays) * 100, 100), 1) : 0;

        return [
            'total_school_days' => $expectedDays,
            'days_present' => $present,
            'days_absent' => $absent,
            'days_late' => $late,
            'days_excused' => $excused,
            'attendance_percentage' => $percentage,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ];
    }

    /**
     * Current vs previous period comparison table for parents/staff.
     */
    public function getComparativeSummaryTable(
        int $studentId,
        ?int $classSectionId,
        string $period = 'week',
        bool $isSubjectWise = false,
        ?int $subjectId = null
    ): array {
        $now = Carbon::now();
        [$currentStart, $currentEnd, $previousStart, $previousEnd, $periodLabel, $previousLabel] =
            $this->resolvePeriodBounds($period, $now);

        $current = $this->getPeriodSummary($studentId, $classSectionId, $currentStart, $currentEnd, $isSubjectWise, $subjectId);
        $previous = $this->getPeriodSummary($studentId, $classSectionId, $previousStart, $previousEnd, $isSubjectWise, $subjectId);

        $metrics = ['total_school_days', 'days_present', 'days_absent', 'days_late', 'attendance_percentage'];
        $rows = [];
        foreach ($metrics as $metric) {
            $cur = $current[$metric] ?? 0;
            $prev = $previous[$metric] ?? 0;
            $rows[] = [
                'metric' => $metric,
                'label' => __('attendance.summary_' . $metric),
                'current' => $cur,
                'previous' => $prev,
                'change' => is_float($cur) || is_float($prev)
                    ? round($cur - $prev, 1)
                    : ($cur - $prev),
            ];
        }

        return [
            'period' => $period,
            'period_label' => $periodLabel,
            'previous_label' => $previousLabel,
            'current' => $current,
            'previous' => $previous,
            'rows' => $rows,
        ];
    }

    private function resolvePeriodBounds(string $period, Carbon $now): array
    {
        return match ($period) {
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
                $now->format('F Y'),
                $now->copy()->subMonth()->format('F Y'),
            ],
            default => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
                __('attendance.this_week') . ' (' . $now->copy()->startOfWeek()->format('d M') . ' – ' . $now->copy()->endOfWeek()->format('d M') . ')',
                __('attendance.last_week') . ' (' . $now->copy()->subWeek()->startOfWeek()->format('d M') . ' – ' . $now->copy()->subWeek()->endOfWeek()->format('d M') . ')',
            ],
        };
    }

    private function fetchRecords(int $studentId, Carbon $start, Carbon $end, bool $isSubjectWise, ?int $subjectId)
    {
        $query = StudentAttendance::where('student_id', $studentId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);

        if ($isSubjectWise && $subjectId) {
            $query->where('subject_id', $subjectId);
        } else {
            $query->whereNull('subject_id');
        }

        return $query->get();
    }

    private function countExpectedDays(?int $classSectionId, Carbon $start, Carbon $end, bool $isSubjectWise, ?int $subjectId): int
    {
        if (!$classSectionId) {
            return 0;
        }

        $query = StudentAttendance::where('class_section_id', $classSectionId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);

        if ($isSubjectWise && $subjectId) {
            $query->where('subject_id', $subjectId);
        } else {
            $query->whereNull('subject_id');
        }

        return (int) $query->distinct('attendance_date')->count('attendance_date');
    }
}