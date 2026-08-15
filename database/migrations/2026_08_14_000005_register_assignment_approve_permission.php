<?php

use App\Enums\RoleEnum;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::where('slug', 'assignments')->first();

        $permission = Permission::firstOrCreate(
            ['name' => 'assignment.approve', 'guard_name' => 'web'],
            ['module_id' => $module->id ?? null]
        );

        if (! $permission->module_id && $module) {
            $permission->update(['module_id' => $module->id]);
        }

        $superAdmin = Role::where('name', RoleEnum::SUPER_ADMIN->value)
            ->whereNull('institution_id')
            ->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission->name);
        }

        // School Admin and Head Officer stand in for the Director role until a
        // school creates its own (e.g. Academic Coordinator) and grants it there.
        Role::whereIn('name', [RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value])
            ->each(function (Role $role) use ($permission) {
                $role->givePermissionTo($permission->name);
            });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', 'assignment.approve')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
