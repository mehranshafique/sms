<?php

namespace App\Services;

use App\Models\StudentAttendance;
use Carbon\Carbon;

class AttendanceAnalyticsService
{
    /**
     * Generate comparative statistics for a given period
     * @param string $period 'week', 'month', 'quarter', 'semester', 'year'
     */
    public function getComparativeStats($studentId, $period = 'week')
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

        // Fetch Data
        $currentRecords = StudentAttendance::where('student_id', $studentId)
            ->whereBetween('attendance_date', [$currentStart, $now])
            ->get();

        $previousRecords = StudentAttendance::where('student_id', $studentId)
            ->whereBetween('attendance_date', [$previousStart, $previousEnd])
            ->get();

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
            
            'participation_rate' => $this->calculateParticipationRate($currentRecords, $now->diffInWeekdays($currentStart) + 1),
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
        $attended = $records->whereNotNull('check_in')->count();
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
}