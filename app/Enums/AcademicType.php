<?php

namespace App\Enums;

enum AcademicType: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case LMD = 'university';
    case VOCATIONAL = 'vocational'; // Added new case

    public static function labels(): array
    {
        return [
            self::PRIMARY->value => __('grade.cycle_primary'),
            self::SECONDARY->value => __('grade.cycle_secondary'),
            self::LMD->value => __('grade.cycle_lmd'),
            self::VOCATIONAL->value => __('grade.cycle_vocational'), // Added label mapping
        ];
    }
}