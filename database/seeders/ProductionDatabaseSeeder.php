<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Safe seeders for production (e-digitex.com).
 * Never includes BulkDummyDataSeeder or demo institutions.
 *
 * Usage:
 *   php artisan db:seed --class=ProductionDatabaseSeeder --force
 */
class ProductionDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            RolePermissionSeeder::class,
            LocationSeeder::class,
            SmsTemplateSeeder::class,
            PlatformSuperAdminSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
