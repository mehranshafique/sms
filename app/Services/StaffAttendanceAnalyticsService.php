<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffAttendanceAnalyticsService
{
    /**
     * @return array{start:Carbon,end:Carbon,prev_start:Carbon,prev_end:Carbon}
     */
    public function periodBounds(string $period = 'week'): array
    {
        $now = Carbon::now();

        return match ($period) {
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy(),
                'prev_start' => $now->copy()->subMonth()->startOfMonth(),
                'prev_end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy(),
                'prev_start' => $now->copy()->subQuarter()->startOfQuarter(),
                'prev_end' => $now->copy()->subQuarter()->endOfQuarter(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy(),
                'prev_start' => $now->copy()->subYear()->startOfYear(),
                'prev_end' => $now->copy()->subYear()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy(),
                'prev_start' => $now->copy()->subWeek()->startOfWeek(),
                'prev_end' => $now->copy()->subWeek()->endOfWeek(),
            ],
        };
    }

    public function dashboard(int $institutionId, string $period = 'week'): array
    {
        $bounds = $this->periodBounds($period);
        $records = StaffAttendance::query()
            ->where('institution_id', $institutionId)
            ->whereBetween('attendance_date', [$bounds['start']->toDateString(), $bounds['end']->toDateString()])
            ->get();

        $activeStaff = Staff::where('institution_id', $institutionId)->where('status', 'active')->count();
        $totals = $this->statusTotals($records);
        $markedDays = $records->pluck('attendance_date')->unique()->count();
        $expectedSlots = max($activeStaff * max($markedDays, 1), 1);

        $trend = $records
            ->groupBy(fn ($r) => Carbon::parse($r->attendance_date)->format('Y-m-d'))
            ->sortKeys()
            ->map(function (Collection $dayRecords, $date) {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d M'),
                    'present' => $dayRecords->whereIn('status', ['present', 'excused', 'half_day'])->count(),
                    'late' => $dayRecords->where('status', 'late')->count(),
                    'absent' => $dayRecords->where('status', 'absent')->count(),
                ];
            })
            ->values()
            ->all();

        $byStaff = $records->groupBy('staff_id')->map(function (Collection $rows, $staffId) {
            $total = max($rows->count(), 1);
            $absent = $rows->where('status', 'absent')->count();
            $late = $rows->where('status', 'late')->count();
            $presentish = $rows->whereIn('status', ['present', 'excused', 'half_day', 'late'])->count();

            return [
                'staff_id' => (int) $staffId,
                'days_marked' => $rows->count(),
                'absent' => $absent,
                'late' => $late,
                'attendance_rate' => round(($presentish / $total) * 100),
                'punctuality' => round(($rows->whereIn('status', ['present', 'excused'])->count() / $total) * 100),
            ];
        });

        $staffMeta = Staff::with('user')
            ->whereIn('id', $byStaff->keys()->all())
            ->get()
            ->keyBy('id');

        $leaderboard = $byStaff
            ->map(function ($stats) use ($staffMeta) {
                $staff = $staffMeta->get($stats['staff_id']);
                $stats['name'] = $staff?->user?->name ?? ('#'.$stats['staff_id']);
                $stats['employee_id'] = $staff?->employee_id;
                $stats['designation'] = $staff?->designation;

                return $stats;
            })
            ->sortByDesc('absent')
            ->values()
            ->all();

        $attendanceRate = $records->isEmpty()
            ? 0
            : round(($records->whereIn('status', ['present', 'late', 'excused', 'half_day'])->count() / max($records->count(), 1)) * 100);

        return [
            'period' => $period,
            'bounds' => $bounds,
            'active_staff' => $activeStaff,
            'records_count' => $records->count(),
            'marked_days' => $markedDays,
            'totals' => $totals,
            'attendance_rate' => $attendanceRate,
            'absence_rate' => $records->isEmpty() ? 0 : round(($totals['absent'] / max($records->count(), 1)) * 100),
            'late_rate' => $records->isEmpty() ? 0 : round(($totals['late'] / max($records->count(), 1)) * 100),
            'trend' => $trend,
            'leaderboard' => $leaderboard,
            'expected_slots' => $expectedSlots,
        ];
    }

    public function staffStats(int $staffId, int $institutionId, string $period = 'week'): array
    {
        $bounds = $this->periodBounds($period);

        $current = StaffAttendance::where('staff_id', $staffId)
            ->where('institution_id', $institutionId)
            ->whereBetween('attendance_date', [$bounds['start']->toDateString(), $bounds['end']->toDateString()])
            ->orderBy('attendance_date', 'desc')
            ->get();

        $previous = StaffAttendance::where('staff_id', $staffId)
            ->where('institution_id', $institutionId)
            ->whereBetween('attendance_date', [$bounds['prev_start']->toDateString(), $bounds['prev_end']->toDateString()])
            ->get();

        $currentTotals = $this->statusTotals($current);
        $previousTotals = $this->statusTotals($previous);

        $currentPunctuality = $this->punctuality($current);
        $previousPunctuality = $this->punctuality($previous);

        $avgArrival = $this->averageArrival($current);
        $prevAvgArrival = $this->averageArrival($previous);

        return [
            'period' => $period,
            'bounds' => $bounds,
            'totals' => $currentTotals,
            'previous_totals' => $previousTotals,
            'current_punctuality' => $currentPunctuality,
            'previous_punctuality' => $previousPunctuality,
            'punctuality_insight' => $this->punctualityInsight($currentPunctuality, $previousPunctuality, $period),
            'current_avg_time' => $avgArrival,
            'previous_avg_time' => $prevAvgArrival,
            'arrival_insight' => $this->arrivalInsight($avgArrival, $prevAvgArrival, $period),
            'attendance_rate' => $current->isEmpty()
                ? 0
                : round(($current->whereIn('status', ['present', 'late', 'excused', 'half_day'])->count() / max($current->count(), 1)) * 100),
            'records' => $current,
        ];
    }

    protected function statusTotals(Collection $records): array
    {
        return [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'excused' => $records->where('status', 'excused')->count(),
            'half_day' => $records->where('status', 'half_day')->count(),
        ];
    }

    protected function punctuality(Collection $records): int
    {
        if ($records->isEmpty()) {
            return 0;
        }

        return (int) round(($records->whereIn('status', ['present', 'excused'])->count() / $records->count()) * 100);
    }

    protected function averageArrival(Collection $records): string
    {
        $mins = [];
        foreach ($records as $record) {
            if (! $record->check_in) {
                continue;
            }
            $time = Carbon::parse($record->check_in);
            $mins[] = ($time->hour * 60) + $time->minute;
        }
        if ($mins === []) {
            return 'N/A';
        }
        $avg = array_sum($mins) / count($mins);

        return sprintf('%02d:%02d', intdiv((int) $avg, 60), ((int) $avg) % 60);
    }

    protected function punctualityInsight(int $current, int $previous, string $period): string
    {
        if ($previous === 0) {
            return __('attendance.not_enough_comparison');
        }
        $diff = $current - $previous;
        $label = __('attendance.period_'.$period);

        if ($diff > 0) {
            return __('attendance.punctuality_up', ['diff' => $diff, 'period' => $label]);
        }
        if ($diff < 0) {
            return __('attendance.punctuality_down', ['diff' => abs($diff), 'period' => $label]);
        }

        return __('attendance.punctuality_same', ['period' => $label]);
    }

    protected function arrivalInsight(string $current, string $previous, string $period): string
    {
        if ($current === 'N/A' || $previous === 'N/A') {
            return __('attendance.not_enough_comparison');
        }

        return __('attendance.avg_arrival_compare', [
            'current' => $current,
            'previous' => $previous,
            'period' => __('attendance.period_'.$period),
        ]);
    }
}
