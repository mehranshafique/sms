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
    public const EMAIL = 'digitex-admin@yopmail.com';

    public const USERNAME = 'digitex-admin';

    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate([
            'name' => RoleEnum::SUPER_ADMIN->value,
            'guard_name' => 'web',
            'institution_id' => null,
        ]);

        $user = User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Digitex Super Admin',
                'username' => self::USERNAME,
                'password' => Hash::make('password'),
                'user_type' => UserType::SUPER_ADMIN->value,
                'is_active' => true,
                'institute_id' => null,
            ]
        );

        if (!$user->hasRole($superAdminRole)) {
            $user->assignRole($superAdminRole);
        }

        $this->command?->info('Platform Super Admin ready: ' . self::EMAIL . ' (password: password)');
    }
}
