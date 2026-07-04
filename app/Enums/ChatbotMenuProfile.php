<?php

namespace App\Enums;

enum ChatbotMenuProfile: string
{
    case STUDENT = 'student';
    case PARENT = 'parent';
    case TEACHER = 'teacher';
    case SCHOOL_ADMIN = 'school_admin';
    case HEAD_OFFICER = 'head_officer';
    case FINANCE = 'finance';
    case SUPER_ADMIN = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Student',
            self::PARENT => 'Parent',
            self::TEACHER => 'Teacher',
            self::SCHOOL_ADMIN => 'Director / School Admin',
            self::HEAD_OFFICER => 'Head Office',
            self::FINANCE => 'Finance',
            self::SUPER_ADMIN => 'Super Admin',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $profile) => [$profile->value => $profile->label()])
            ->all();
    }

    public function isStaffProfile(): bool
    {
        return in_array($this->value, [
            self::TEACHER->value,
            self::SCHOOL_ADMIN->value,
            self::HEAD_OFFICER->value,
            self::FINANCE->value,
            self::SUPER_ADMIN->value,
        ], true);
    }
}
