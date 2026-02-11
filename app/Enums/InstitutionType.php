<?php

namespace App\Enums;

enum InstitutionType: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case UNIVERSITY = 'university';
    case VOCATIONAL = 'vocational';
    // case MIXED = 'mixed';

    public function label(): string
    {
        return match($this) {
            self::PRIMARY => __('institute.primary_school'),
            self::SECONDARY => __('institute.secondary_school'),
            self::UNIVERSITY => __('institute.university'),
            self::VOCATIONAL => __('institute.mixed_level'), // Ensure translation key exists or add fallback
            // self::MIXED => __('institute.mixed_level'),
        };
    }
}