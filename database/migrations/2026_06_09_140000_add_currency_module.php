<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'currency')->value('id');

        if (!$moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'Currency',
                'slug' => 'currency',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['view', 'update'] as $action) {
            $exists = DB::table('permissions')
                ->where('name', "currency.{$action}")
                ->where('guard_name', 'web')
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => "currency.{$action}",
                    'guard_name' => 'web',
                    'module_id' => $moduleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $permIds = DB::table('permissions')
            ->whereIn('name', ['currency.view', 'currency.update'])
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'School Admin', 'Head Officer'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permId,
                    'role_id' => $roleId,
                ]);
            }
        }

        InstitutionSetting::where('key', 'enabled_modules')->each(function ($setting) {
            $modules = json_decode($setting->value, true);
            if (!is_array($modules)) {
                $modules = [];
            }
            if (!in_array('currency', $modules, true)) {
                $modules[] = 'currency';
                $setting->update(['value' => json_encode(array_values($modules))]);
            }
        });
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'like', 'currency.%')->delete();
        DB::table('modules')->where('slug', 'currency')->delete();

        InstitutionSetting::where('key', 'enabled_modules')->each(function ($setting) {
            $modules = json_decode($setting->value, true);
            if (!is_array($modules)) {
                return;
            }
            $modules = array_values(array_filter($modules, fn ($m) => $m !== 'currency'));
            $setting->update(['value' => json_encode($modules)]);
        });
    }
};
