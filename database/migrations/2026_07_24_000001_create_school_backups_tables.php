<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\InstitutionSetting;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('type', 20)->default('manual'); // manual|scheduled
            $table->string('status', 20)->default('pending'); // pending|running|completed|failed
            $table->string('disk_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('drive_file_id')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('include_files')->default(true);
            $table->text('error_message')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'created_at']);
        });

        Schema::create('school_backup_drive_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('google_email')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('folder_id')->nullable();
            $table->string('folder_name')->nullable();
            $table->timestamps();
        });

        $moduleId = DB::table('modules')->where('slug', 'school_backups')->value('id');
        if (!$moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'School Backups',
                'slug' => 'school_backups',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['view', 'create', 'manage'] as $action) {
            $name = "school_backup.{$action}";
            $exists = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'module_id' => $moduleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('permissions')->where('name', $name)->update(['module_id' => $moduleId]);
            }
        }

        $permIds = DB::table('permissions')
            ->whereIn('name', ['school_backup.view', 'school_backup.create', 'school_backup.manage'])
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
            if (!in_array('school_backups', $modules, true)) {
                $modules[] = 'school_backups';
                $setting->update(['value' => json_encode(array_values($modules))]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_backup_drive_accounts');
        Schema::dropIfExists('school_backups');

        DB::table('permissions')->where('name', 'like', 'school_backup.%')->delete();
        DB::table('modules')->where('slug', 'school_backups')->delete();

        InstitutionSetting::where('key', 'enabled_modules')->each(function ($setting) {
            $modules = json_decode($setting->value, true);
            if (!is_array($modules)) {
                return;
            }
            $modules = array_values(array_filter($modules, fn ($m) => $m !== 'school_backups'));
            $setting->update(['value' => json_encode($modules)]);
        });
    }
};
