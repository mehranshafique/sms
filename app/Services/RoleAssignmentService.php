<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class RoleAssignmentService
{
    public function resolveForInstitution(string $name, int $institutionId, string $guard = 'web'): Role
    {
        $role = Role::query()
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$role) {
            abort(422, __('roles.messages.role_not_found_for_institution', ['role' => $name]));
        }

        return $role;
    }

    public function resolveByIdForInstitution(int $roleId, int $institutionId): Role
    {
        $role = Role::query()->findOrFail($roleId);
        $this->assertRoleBelongsToInstitution($role, $institutionId);

        return $role;
    }

    /**
     * Resolve a role by id or name, always scoped to the institution.
     */
    public function resolve(string|int $role, int $institutionId, string $guard = 'web'): Role
    {
        if (is_numeric($role)) {
            return $this->resolveByIdForInstitution((int) $role, $institutionId);
        }

        return $this->resolveForInstitution((string) $role, $institutionId, $guard);
    }

    public function assertRoleBelongsToInstitution(Role $role, int $institutionId): void
    {
        if ($role->name === RoleEnum::SUPER_ADMIN->value) {
            abort(403, __('roles.messages.unauthorized_access'));
        }

        if ($role->institution_id === null) {
            abort(403, __('roles.messages.cannot_assign_global_role'));
        }

        if ((int) $role->institution_id !== (int) $institutionId) {
            abort(403, __('roles.messages.cross_institution_role'));
        }
    }

    public function assign(User $user, string|int $role, int $institutionId): Role
    {
        $resolved = $this->resolve($role, $institutionId);
        $before = $user->roles->pluck('id')->sort()->values()->all();

        $user->assignRole($resolved);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->unsetRelation('roles');
        $after = $user->roles->pluck('id')->sort()->values()->all();

        AuditLogger::log(
            'Assign Role',
            'Roles',
            "Assigned role \"{$resolved->name}\" (#{$resolved->id}) to user #{$user->id}",
            ['role_ids' => $before],
            ['role_ids' => $after, 'assigned_role_id' => $resolved->id, 'institution_id' => $institutionId]
        );

        return $resolved;
    }

    /**
     * @param  array<int, string|int>  $roles
     * @return Collection<int, Role>
     */
    public function sync(User $user, array $roles, int $institutionId): Collection
    {
        $resolved = collect($roles)
            ->filter(fn ($r) => $r !== null && $r !== '')
            ->map(fn ($r) => $this->resolve($r, $institutionId))
            ->unique('id')
            ->values();

        $before = $user->roles->pluck('id')->sort()->values()->all();

        $user->syncRoles($resolved->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->unsetRelation('roles');
        $after = $user->roles->pluck('id')->sort()->values()->all();

        AuditLogger::log(
            'Sync Roles',
            'Roles',
            "Synced roles for user #{$user->id}",
            ['role_ids' => $before],
            [
                'role_ids' => $after,
                'institution_id' => $institutionId,
                'synced_role_ids' => $resolved->pluck('id')->all(),
            ]
        );

        return $resolved;
    }

    /**
     * Ensure system roles exist for an institution and copy permissions from global templates.
     *
     * @param  list<string>|null  $roleNames
     * @return Collection<int, Role>
     */
    public function ensureInstitutionRoles(int $institutionId, ?array $roleNames = null): Collection
    {
        $roleNames ??= [
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::TEACHER->value,
            RoleEnum::STUDENT->value,
            RoleEnum::GUARDIAN->value,
        ];

        $created = collect();

        foreach ($roleNames as $roleName) {
            $role = Role::firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'institution_id' => $institutionId,
                ]
            );

            $this->copyPermissionsFromTemplate($role);
            $created->push($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }

    public function copyPermissionsFromTemplate(Role $institutionRole): void
    {
        if ($institutionRole->institution_id === null) {
            return;
        }

        $template = Role::query()
            ->templates()
            ->where('name', $institutionRole->name)
            ->where('guard_name', $institutionRole->guard_name ?: 'web')
            ->first();

        if (!$template) {
            return;
        }

        // Only seed empty institution roles so manual school customizations are preserved.
        if ($institutionRole->permissions()->count() > 0) {
            return;
        }

        $institutionRole->syncPermissions($template->permissions);
    }

    public function forceSyncPermissionsFromTemplate(Role $institutionRole): void
    {
        if ($institutionRole->institution_id === null) {
            return;
        }

        $template = Role::query()
            ->templates()
            ->where('name', $institutionRole->name)
            ->where('guard_name', $institutionRole->guard_name ?: 'web')
            ->first();

        if (!$template) {
            return;
        }

        $institutionRole->syncPermissions($template->permissions);
    }
}
