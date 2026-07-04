<?php

namespace App\Services\Chatbot;

use App\Enums\ChatbotMenuProfile;
use App\Models\ChatbotKeyword;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\User;

class ChatbotAccessService
{
    public function resolveBuiltinMenuProfile(string $keyword): ?string
    {
        $map = config('chatbot.builtin_keyword_menu_profiles', []);

        return $map[strtolower(trim($keyword))] ?? null;
    }

    public function inferMenuProfileFromKeyword(string $keyword): string
    {
        return $this->resolveBuiltinMenuProfile($keyword)
            ?? ChatbotMenuProfile::STUDENT->value;
    }

    public function assertStudentMatchesMenuProfile(Student $student, string $menuProfile): bool
    {
        return $menuProfile === ChatbotMenuProfile::STUDENT->value;
    }

    public function assertParentMatchesMenuProfile(StudentParent $parent, string $menuProfile): bool
    {
        return $menuProfile === ChatbotMenuProfile::PARENT->value;
    }

    public function userCanAccessKeyword(User $user, ChatbotKeyword $keyword): bool
    {
        $keyword->loadMissing('allowedRoles');

        $allowedRoleIds = $keyword->allowedRoles->pluck('id');

        if ($allowedRoleIds->isNotEmpty()) {
            return $user->roles()->whereIn('id', $allowedRoleIds)->exists();
        }

        return $this->userCanAccessMenuProfile($user, $keyword->menu_profile);
    }

    public function userCanAccessMenuProfile(User $user, string $menuProfile): bool
    {
        $defaults = config("chatbot.menu_profile_default_roles.{$menuProfile}", []);

        if ($defaults !== []) {
            return $user->hasAnyRole($defaults);
        }

        return $this->userCanAccessMenuProfileByPermission($user, $menuProfile);
    }

    public function userCanAccessMenuProfileByPermission(User $user, string $menuProfile): bool
    {
        $permissions = config("chatbot.menu_profile_permission_fallback.{$menuProfile}", []);

        if ($permissions === []) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
