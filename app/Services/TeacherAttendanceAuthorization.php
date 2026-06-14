<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TeacherAttendanceAuthorization
{
    public static function allowedRoles(): array
    {
        return ['Super Admin', 'Head Officer', 'School Admin', 'Teacher', 'Staff'];
    }

    public static function denyUnlessAllowed(User $user): ?JsonResponse
    {
        if (!$user->hasRole(self::allowedRoles())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Teacher or staff access required.',
            ], 403);
        }

        return null;
    }

    public static function canAccessClassSection(User $user, ClassSection $classSection, ?int $subjectId = null): bool
    {
        if ($user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            return true;
        }

        $staff = $user->staff;
        if (!$staff) {
            return false;
        }

        if ((int) $classSection->staff_id === (int) $staff->id) {
            return true;
        }

        if ($classSection->timetables()->where('teacher_id', $staff->id)->exists()) {
            return true;
        }

        $subjectQuery = $classSection->classSubjects()->where('teacher_id', $staff->id);
        if ($subjectId) {
            $subjectQuery->where('subject_id', $subjectId);
        }

        return $subjectQuery->exists();
    }

    public static function denyUnlessClassAccess(User $user, ClassSection $classSection, ?int $subjectId = null): ?JsonResponse
    {
        if ($denied = self::denyUnlessAllowed($user)) {
            return $denied;
        }

        if (!self::canAccessClassSection($user, $classSection, $subjectId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this class.',
            ], 403);
        }

        return null;
    }
}
