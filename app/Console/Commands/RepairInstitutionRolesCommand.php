<?php

namespace App\Console\Commands;

use App\Enums\RoleEnum;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RepairInstitutionRolesCommand extends Command
{
    protected $signature = 'roles:repair-institutions
                            {--dry-run : Show what would change without writing}
                            {--force-sync-perms : Overwrite institution role permissions from global templates}';

    protected $description = 'Ensure one system role per school, reattach users from global templates, and sync permissions';

    public function handle(RoleAssignmentService $roleAssignment): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $forceSync = (bool) $this->option('force-sync-perms');

        $this->info($dryRun ? 'Dry run — no changes will be saved.' : 'Repairing institution roles…');

        $stats = [
            'institutions' => 0,
            'roles_created' => 0,
            'duplicates_merged' => 0,
            'users_reattached' => 0,
            'perms_synced' => 0,
        ];

        Institution::query()->orderBy('id')->chunkById(50, function ($institutions) use ($roleAssignment, $dryRun, $forceSync, &$stats) {
            foreach ($institutions as $institution) {
                $stats['institutions']++;
                $institutionId = (int) $institution->id;

                if (!$dryRun) {
                    $beforeCount = Role::forInstitution($institutionId)->count();
                    $roleAssignment->ensureInstitutionRoles($institutionId);
                    $afterCount = Role::forInstitution($institutionId)->count();
                    $stats['roles_created'] += max(0, $afterCount - $beforeCount);
                } else {
                    foreach ([
                        RoleEnum::SCHOOL_ADMIN->value,
                        RoleEnum::HEAD_OFFICER->value,
                        RoleEnum::TEACHER->value,
                        RoleEnum::STUDENT->value,
                        RoleEnum::GUARDIAN->value,
                    ] as $name) {
                        $exists = Role::forInstitution($institutionId)->where('name', $name)->exists();
                        if (!$exists) {
                            $stats['roles_created']++;
                            $this->line("  [create] {$institution->code}: {$name}");
                        }
                    }
                }

                $stats['duplicates_merged'] += $this->mergeDuplicateRoles($institutionId, $dryRun);
                $stats['users_reattached'] += $this->reattachGlobalAssignments($institutionId, $roleAssignment, $dryRun);

                if ($forceSync) {
                    $stats['perms_synced'] += $this->syncPermissions($institutionId, $roleAssignment, $dryRun, true);
                } else {
                    $stats['perms_synced'] += $this->syncPermissions($institutionId, $roleAssignment, $dryRun, false);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function mergeDuplicateRoles(int $institutionId, bool $dryRun): int
    {
        $merged = 0;

        $duplicates = Role::forInstitution($institutionId)
            ->select('name', 'guard_name', DB::raw('COUNT(*) as c'))
            ->groupBy('name', 'guard_name')
            ->having('c', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $roles = Role::forInstitution($institutionId)
                ->where('name', $dup->name)
                ->where('guard_name', $dup->guard_name)
                ->orderBy('id')
                ->get();

            $keep = $roles->first();
            $extras = $roles->slice(1);

            foreach ($extras as $extra) {
                $this->line("  [merge] institution #{$institutionId}: {$dup->name} #{$extra->id} → #{$keep->id}");
                $merged++;

                if ($dryRun) {
                    continue;
                }

                DB::table(config('permission.table_names.model_has_roles'))
                    ->where('role_id', $extra->id)
                    ->orderBy('model_id')
                    ->chunk(200, function ($rows) use ($keep) {
                        foreach ($rows as $row) {
                            $exists = DB::table(config('permission.table_names.model_has_roles'))
                                ->where('role_id', $keep->id)
                                ->where('model_type', $row->model_type)
                                ->where('model_id', $row->model_id)
                                ->exists();

                            if ($exists) {
                                DB::table(config('permission.table_names.model_has_roles'))
                                    ->where('role_id', $row->role_id)
                                    ->where('model_type', $row->model_type)
                                    ->where('model_id', $row->model_id)
                                    ->delete();
                            } else {
                                DB::table(config('permission.table_names.model_has_roles'))
                                    ->where('role_id', $row->role_id)
                                    ->where('model_type', $row->model_type)
                                    ->where('model_id', $row->model_id)
                                    ->update(['role_id' => $keep->id]);
                            }
                        }
                    });

                $permIds = DB::table(config('permission.table_names.role_has_permissions'))
                    ->where('role_id', $extra->id)
                    ->pluck('permission_id');

                foreach ($permIds as $permissionId) {
                    DB::table(config('permission.table_names.role_has_permissions'))->insertOrIgnore([
                        'permission_id' => $permissionId,
                        'role_id' => $keep->id,
                    ]);
                }

                DB::table(config('permission.table_names.role_has_permissions'))
                    ->where('role_id', $extra->id)
                    ->delete();

                $extra->delete();
            }
        }

        return $merged;
    }

    private function reattachGlobalAssignments(int $institutionId, RoleAssignmentService $roleAssignment, bool $dryRun): int
    {
        $reattached = 0;
        $userModel = User::class;

        $globalRoles = Role::templates()
            ->where('name', '!=', RoleEnum::SUPER_ADMIN->value)
            ->get();

        if ($globalRoles->isEmpty()) {
            return 0;
        }

        $globalIds = $globalRoles->pluck('id');

        $userIds = DB::table(config('permission.table_names.model_has_roles'))
            ->whereIn('role_id', $globalIds)
            ->where('model_type', $userModel)
            ->pluck('model_id')
            ->unique();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->where('institute_id', $institutionId)
            ->get();

        foreach ($users as $user) {
            $attachedGlobal = $user->roles()
                ->whereNull('roles.institution_id')
                ->where('roles.name', '!=', RoleEnum::SUPER_ADMIN->value)
                ->get();

            foreach ($attachedGlobal as $globalRole) {
                $this->line("  [reattach] user #{$user->id} {$globalRole->name}: global #{$globalRole->id} → institution #{$institutionId}");
                $reattached++;

                if ($dryRun) {
                    continue;
                }

                $institutionRole = $roleAssignment->resolveForInstitution($globalRole->name, $institutionId);
                $user->removeRole($globalRole);
                $user->assignRole($institutionRole);
            }
        }

        return $reattached;
    }

    private function syncPermissions(int $institutionId, RoleAssignmentService $roleAssignment, bool $dryRun, bool $force): int
    {
        $synced = 0;

        $roles = Role::forInstitution($institutionId)->get();
        foreach ($roles as $role) {
            $template = Role::templates()->where('name', $role->name)->first();
            if (!$template) {
                continue;
            }

            if (!$force && $role->permissions()->count() > 0) {
                continue;
            }

            $synced++;
            $this->line("  [perms] institution #{$institutionId}: {$role->name}");

            if (!$dryRun) {
                if ($force) {
                    $roleAssignment->forceSyncPermissionsFromTemplate($role);
                } else {
                    $roleAssignment->copyPermissionsFromTemplate($role);
                }
            }
        }

        return $synced;
    }
}
