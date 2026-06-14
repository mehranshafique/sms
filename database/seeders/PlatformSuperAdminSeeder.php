<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Ensures the platform Super Admin account exists (safe to run multiple times).
 */
class PlatformSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('platform.platform_admin_email', 'digitex-admin@yopmail.com');
        $username = config('platform.platform_admin_username', 'digitex-admin');
        $password = config('platform.platform_admin_password');

        if (empty($password)) {
            $password = app()->environment('local', 'testing') ? 'password' : null;
        }

        if (empty($password)) {
            $this->command?->warn('Platform Super Admin skipped: set PLATFORM_ADMIN_PASSWORD in .env for production seeding.');
            return;
        }

        $superAdminRole = Role::firstOrCreate([
            'name' => RoleEnum::SUPER_ADMIN->value,
            'guard_name' => 'web',
            'institution_id' => null,
        ]);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Digitex Super Admin',
                'username' => $username,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $user->forceFill([
            'user_type' => UserType::SUPER_ADMIN->value,
            'is_active' => true,
            'institute_id' => null,
        ])->save();

        if (!$user->hasRole($superAdminRole)) {
            $user->assignRole($superAdminRole);
        }

        $this->command?->info("Platform Super Admin ready: {$email}");
    }

    public static function email(): string
    {
        return config('platform.platform_admin_email', 'digitex-admin@yopmail.com');
    }
}
