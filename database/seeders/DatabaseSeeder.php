<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Disable Foreign Key Checks to prevent constraint errors during refresh
        Schema::disableForeignKeyConstraints();

        // 2. Truncate Tables (Optional if using migrate:fresh, but good for safety)
        // DB::table('users')->truncate();
        // DB::table('roles')->truncate();
        // ... other tables

        // 3. Run Seeders in Order
        $this->call([
            RolePermissionSeeder::class, // Creates Super Admin & Roles
            // BulkDummyDataSeeder::class,
            // SmsTemplateSeeder::class,
            // LocationSeeder::class,
            // Add other seeders here if you have them
            // InstitutionSeeder::class, 
        ]);

        // 4. Re-enable Foreign Key Checks
        Schema::enableForeignKeyConstraints();
    }
}
