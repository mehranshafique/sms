<?php

namespace App\Enums;

enum UserType: int
{
    case SUPER_ADMIN = 1;
    case HEAD_OFFICER = 2;
    case BRANCH_ADMIN = 3;
    case STAFF = 4;
    case STUDENT = 5;
    case GUARDIAN = 6; // Changed from PARENT to GUARDIAN to match Controller
    case SCHOOL_ADMIN = 7;

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => __('enums.user_type.super_admin'),
            self::HEAD_OFFICER => __('enums.user_type.head_officer'),
            self::BRANCH_ADMIN => __('enums.user_type.branch_admin'),
            self::STAFF => __('enums.user_type.staff'),
            self::STUDENT => __('enums.user_type.student'),
            self::GUARDIAN => __('enums.user_type.parent'), // Label usually remains 'Parent' in UI
            self::SCHOOL_ADMIN => 'School Admin',
        };
    }
}