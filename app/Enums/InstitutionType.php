<?php

namespace App\Enums;

enum InstitutionType: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case UNIVERSITY = 'university';
    case VOCATIONAL = 'vocational';
    case MIXED = 'mixed';

    public function label(): string
    {
        return match($this) {
            self::PRIMARY => __('institute.primary_school'),
            self::SECONDARY => __('institute.secondary_school'),
            self::UNIVERSITY => __('institute.university'),
            self::VOCATIONAL => __('institute.vocational_school'),
            self::MIXED => __('institute.mixed_level'),
        };
    }
}