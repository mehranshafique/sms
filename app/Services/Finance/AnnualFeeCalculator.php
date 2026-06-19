<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;
use App\Models\StudentEnrollment;

class AnnualFeeCalculator
{
    /**
     * Net annual fee for an enrollment (matches Student Finance dashboard logic).
     */
    public function forEnrollment(StudentEnrollment $enrollment): float
    {
        $gross = $this->grossForEnrollment($enrollment);
        if ($gross <= 0) {
            return 0.0;
        }

        $discount = 0.0;
        if ($enrollment->discount_amount > 0) {
            if ($enrollment->discount_type === 'percentage') {
                $discount = ($gross * (float) $enrollment->discount_amount) / 100;
            } else {
                $discount = (float) $enrollment->discount_amount;
            }
        }

        return max(0, $gross - $discount);
    }

    public function grossForEnrollment(StudentEnrollment $enrollment): float
    {
        $sessionId = $enrollment->academic_session_id;
        $institutionId = $enrollment->institution_id
            ?? $enrollment->student?->institution_id;

        if (!$sessionId || !$institutionId) {
            return 0.0;
        }

        $gradeId = $enrollment->grade_level_id
            ?? $enrollment->classSection?->grade_level_id;

        if (!$gradeId) {
            return 0.0;
        }

        $globalFee = FeeStructure::where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->where('payment_mode', 'global')
            ->where(function ($q) use ($enrollment, $gradeId) {
                if ($enrollment->class_section_id) {
                    $q->where('class_section_id', $enrollment->class_section_id);
                }
                $q->orWhere('grade_level_id', $gradeId);
            })
            ->sum('amount');

        if ($globalFee > 0) {
            return (float) $globalFee;
        }

        return (float) FeeStructure::where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->where('grade_level_id', $gradeId)
            ->where('payment_mode', 'installment')
            ->sum('amount');
    }
}
