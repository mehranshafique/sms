<?php

namespace App\Enums;

enum ChatbotParticipantType: string
{
    case STUDENT = 'student';
    case PARENT = 'parent';
    case STAFF_USER = 'staff_user';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
