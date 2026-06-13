<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Production: run only the seeders under "Required for production".
     * Skip BulkDummyDataSeeder (and usually LmdProgramTemplateSeeder) on live servers.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            // -----------------------------------------------------------------
            // Required for production (fresh install / migrate:fresh --seed)
            // -----------------------------------------------------------------
            RolePermissionSeeder::class, // Roles, permissions, modules, Super Admin (digitex-admin@yopmail.com — change password after deploy)
            LocationSeeder::class,       // Countries, states, and cities reference data (address dropdowns)
            SmsTemplateSeeder::class,    // Default SMS templates (payment, welcome, notices, etc.)

            // -----------------------------------------------------------------
            // Not required for production — dev, demo, and local staging only
            // -----------------------------------------------------------------
            BulkDummyDataSeeder::class,  // Fake institutions, students, staff, invoices, exams, etc. (Faker data)

            // -----------------------------------------------------------------
            // Optional — not required for production unless you deploy LMD universities
            // -----------------------------------------------------------------
            LmdProgramTemplateSeeder::class, // Licence/Master LMD program templates; skips if no university-type institution exists

            // Always last — guarantees Super Admin exists even if a prior seeder cleared users
            PlatformSuperAdminSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
