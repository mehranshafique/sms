<?php

use App\Enums\RoleEnum;
use App\Models\Institution;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const NURSE_ROLE = 'Nurse';

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::updateOrCreate(
            ['slug' => 'medical_records'],
            ['name' => 'Medical Records']
        );

        $permissionNames = [];
        foreach (['view', 'viewAny', 'create', 'update', 'delete'] as $action) {
            $name = "medical_record.{$action}";
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['module_id' => $module->id]
            );

            if (! $permission->module_id) {
                $permission->update(['module_id' => $module->id]);
            }

            $permissionNames[] = $name;
        }

        $superAdmin = Role::where('name', RoleEnum::SUPER_ADMIN->value)->whereNull('institution_id')->first();
        $superAdmin?->givePermissionTo($permissionNames);

        Role::whereIn('name', [RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value])
            ->each(fn (Role $role) => $role->givePermissionTo($permissionNames));

        // The nurse only needs the medical record plus enough student access to
        // find the child; nothing else.
        $nursePermissions = array_merge($permissionNames, ['student.view', 'student.viewAny']);

        $template = Role::firstOrCreate([
            'name' => self::NURSE_ROLE,
            'guard_name' => 'web',
            'institution_id' => null,
        ]);
        $template->givePermissionTo(
            Permission::whereIn('name', $nursePermissions)->pluck('name')->all()
        );

        foreach (Institution::pluck('id') as $institutionId) {
            $role = Role::firstOrCreate([
                'name' => self::NURSE_ROLE,
                'guard_name' => 'web',
                'institution_id' => $institutionId,
            ]);

            if ($role->permissions()->count() === 0) {
                $role->syncPermissions($template->permissions);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::where('name', self::NURSE_ROLE)->delete();
        Permission::where('name', 'like', 'medical_record.%')->delete();
        Module::where('slug', 'medical_records')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
