<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Collection;

class ActiveRoleService
{
    /** Roles that should not appear in the switcher for staff UX. */
    private const HIDDEN_SWITCH_ROLES = [
        RoleEnum::SUPER_ADMIN->value,
    ];

    /** Parent/student portal personas — must not exercise staff/admin capabilities. */
    public const PORTAL_ROLES = [
        RoleEnum::STUDENT->value,
        RoleEnum::GUARDIAN->value,
    ];

    /** Institution / platform management personas. */
    public const ADMIN_ROLES = [
        RoleEnum::SUPER_ADMIN->value,
        RoleEnum::SCHOOL_ADMIN->value,
        RoleEnum::HEAD_OFFICER->value,
    ];

    public function availableRoles(User $user): Collection
    {
        $priority = $this->rolePriority();

        return $user->roles
            ->pluck('name')
            ->unique()
            ->filter(fn (string $role) => !in_array($role, self::HIDDEN_SWITCH_ROLES, true))
            ->sortBy(function (string $role) use ($priority) {
                $index = array_search($role, $priority, true);

                return $index === false ? 100 + ord($role[0] ?? 'Z') : $index;
            })
            ->values();
    }

    public function getActiveRole(User $user): ?string
    {
        $sessionRole = session('active_role');
        if ($sessionRole && $user->hasRole($sessionRole)) {
            return $sessionRole;
        }

        return $this->defaultRole($user);
    }

    public function setActiveRole(User $user, string $roleName): void
    {
        if (!$user->hasRole($roleName)) {
            abort(403, __('role.invalid_role_switch'));
        }

        session(['active_role' => $roleName]);
    }

    public function clearActiveRole(): void
    {
        session()->forget('active_role');
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

    public function isPortalPersona(User $user): bool
    {
        return $this->userActsAs($user, self::PORTAL_ROLES);
    }

    public function isAdminPersona(User $user): bool
    {
        if ($this->isPortalPersona($user)) {
            return false;
        }

        // Super Admin is hidden from the switcher; treat as admin unless in a portal persona.
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $this->userActsAs($user, [
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
        ]);
    }

    /**
     * Staff/module permissions may be assigned via Spatie on dual-role accounts.
     * Portal personas must not inherit those while acting as Student/Guardian.
     */
    public function canUseStaffCapabilities(User $user): bool
    {
        return ! $this->isPortalPersona($user);
    }

    private function defaultRole(User $user): ?string
    {
        foreach ($this->rolePriority() as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return $user->roles->first()?->name;
    }

    /** @return list<string> */
    private function rolePriority(): array
    {
        return [
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::TEACHER->value,
            RoleEnum::STAFF->value,
            RoleEnum::GATE_ATTENDANT->value,
            RoleEnum::STUDENT->value,
            RoleEnum::GUARDIAN->value,
        ];
    }
}
