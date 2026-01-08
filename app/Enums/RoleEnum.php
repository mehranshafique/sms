<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'Super Admin';
    case SCHOOL_ADMIN = 'School Admin';
    case HEAD_OFFICER = 'Head Officer';
    case TEACHER = 'Teacher';
    case STUDENT = 'Student';
    // ... add others as needed
}