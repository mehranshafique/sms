<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'Super Admin';
    case SCHOOL_ADMIN = 'School Admin';
    case HEAD_OFFICER = 'Head Officer';
    case TEACHER = 'Teacher';
    case STUDENT = 'Student';
    case GUARDIAN = 'Guardian'; // Added Guardian Role
    case STAFF = 'Staff';       // Ensure Staff is also present if used
}