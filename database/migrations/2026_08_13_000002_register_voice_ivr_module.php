<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::updateOrCreate(
            ['slug' => 'voice_ivr'],
            ['name' => 'Voice Ivr']
        );

        $permissionNames = [];
        foreach (['view', 'manage'] as $action) {
            $name = "voice_ivr.{$action}";
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['module_id' => $module->id]
            );
            if (! $permission->module_id) {
                $permission->update(['module_id' => $module->id]);
            }
            $permissionNames[] = $name;
        }

        $superAdmin = Role::where('name', RoleEnum::SUPER_ADMIN->value)
            ->whereNull('institution_id')
            ->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissionNames);
        }

        Role::where('name', RoleEnum::SCHOOL_ADMIN->value)
            ->each(function (Role $role) use ($permissionNames) {
                $role->givePermissionTo($permissionNames);
            });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['voice_ivr.view', 'voice_ivr.manage'])->delete();
        Module::where('slug', 'voice_ivr')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
