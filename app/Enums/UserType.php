<?php

namespace App\Enums;

enum UserType: int
{
    case SUPER_ADMIN = 1;
    case INSTITUTION_ADMIN = 2; // or Head Officer
    case STAFF = 3;
    case TEACHER = 4;
    case STUDENT = 5;
    case PARENT = 6;

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::INSTITUTION_ADMIN => 'Institution Admin',
            self::STAFF => 'Staff',
            self::TEACHER => 'Teacher',
            self::STUDENT => 'Student',
            self::PARENT => 'Parent',
        };
    }
}