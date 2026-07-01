<?php

namespace App\Services\Mobile;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MobileActiveRoleService
{
    private const CACHE_PREFIX = 'mobile_active_role:';

    private const HIDDEN_SWITCH_ROLES = [
        RoleEnum::SUPER_ADMIN->value,
    ];

    public function availableRoles(User $user): Collection
    {
        return $user->roles
            ->pluck('name')
            ->unique()
            ->filter(fn (string $role) => !in_array($role, self::HIDDEN_SWITCH_ROLES, true))
            ->values();
    }

    public function getActiveRole(User $user): ?string
    {
        $cached = Cache::get(self::CACHE_PREFIX . $user->id);
        if ($cached && $user->hasRole($cached)) {
            return $cached;
        }

        return $this->defaultRole($user);
    }

    public function setActiveRole(User $user, string $roleName): void
    {
        if (!$user->hasRole($roleName)) {
            abort(403, __('role.invalid_role_switch'));
        }

        Cache::put(self::CACHE_PREFIX . $user->id, $roleName, now()->addYear());
    }

    public function clearActiveRole(User $user): void
    {
        Cache::forget(self::CACHE_PREFIX . $user->id);
    }

    public function userActsAs(User $user, string|array $roles): bool
    {
        $active = $this->getActiveRole($user);

        if ($active) {
            return $user->hasRole($roles) && (
                is_array($roles) ? in_array($active, $roles, true) : $active === $roles
            );
        }

        return $user->hasRole($roles);
    }

    private function defaultRole(User $user): ?string
    {
        $priority = [
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::TEACHER->value,
            RoleEnum::STUDENT->value,
            RoleEnum::GUARDIAN->value,
        ];

        foreach ($priority as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return $user->roles->first()?->name;
    }
}
