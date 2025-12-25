<?php

namespace App\Enums;

enum UserType: int
{
    case SUPER_ADMIN = 1;
    case HEAD_OFFICER = 2; // School Admin
    case BRANCH_ADMIN = 3;
    case STAFF = 4;
    case STUDENT = 5;
    case PARENT = 6;

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => __('enums.user_type.super_admin'),
            self::HEAD_OFFICER => __('enums.user_type.head_officer'),
            self::BRANCH_ADMIN => __('enums.user_type.branch_admin'),
            self::STAFF => __('enums.user_type.staff'),
            self::STUDENT => __('enums.user_type.student'),
            self::PARENT => __('enums.user_type.parent'),
        };
    }
}