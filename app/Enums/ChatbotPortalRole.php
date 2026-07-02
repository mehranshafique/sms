<?php

namespace App\Enums;

enum ChatbotPortalRole: string
{
    case STUDENT = 'student';
    case PARENT = 'parent';
    case TEACHER = 'teacher';
    case SCHOOL_ADMIN = 'school_admin';
    case HEAD_OFFICER = 'head_officer';
    case FINANCE = 'finance';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Student',
            self::PARENT => 'Parent',
            self::TEACHER => 'Teacher',
            self::SCHOOL_ADMIN => 'Director / School Admin',
            self::HEAD_OFFICER => 'Head Office',
            self::FINANCE => 'Finance',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
