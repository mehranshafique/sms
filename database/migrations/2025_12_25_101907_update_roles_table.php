<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration fixes the "Duplicate entry" error by ensuring the unique 
     * constraint on roles includes the institution_id.
     */
    public function up(): void
    {
        // 1. Ensure columns exist before modifying constraints
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'institution_id')) {
                $table->unsignedBigInteger('institution_id')->nullable()->after('id');
                // Use a try-catch for foreign key in case table/id mismatch exists in some environments
                try {
                    $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
                } catch (\Exception $e) {}
            }

            if (!Schema::hasColumn('roles', 'can_delete')) {
                $table->tinyInteger('can_delete')->default(1)->after('guard_name');
            }
        });

        // 2. Drop the conflicting standard unique index (name, guard_name)
        // We use a helper because the index name might differ depending on the driver
        $this->dropUniqueIfExists('roles', 'roles_name_guard_name_unique');
        $this->dropUniqueIfExists('roles', ['name', 'guard_name']);

        // 3. Drop any previously attempted multi-tenant unique indexes to prevent "Duplicate key name"
        $this->dropUniqueIfExists('roles', 'roles_name_guard_inst_unique');

        // 4. Add the definitive composite unique index
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['name', 'guard_name', 'institution_id'], 'roles_name_guard_inst_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop the institution-scoped unique index
            $this->dropUniqueIfExists('roles', 'roles_name_guard_inst_unique');

            // Safely drop columns
            if (Schema::hasColumn('roles', 'institution_id')) {
                // Drop foreign key first
                try {
                    $table->dropForeign(['institution_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('institution_id');
            }

            if (Schema::hasColumn('roles', 'can_delete')) {
                $table->dropColumn('can_delete');
            }

            // NOTE: We do NOT re-add the strict unique constraint ['name', 'guard_name'] 
            // in down() because if duplicate names currently exist for different 
            // institutions, the rollback will fail and break the database.
        });
    }

    /**
     * Helper to drop a unique index safely
     */
    private function dropUniqueIfExists(string $tableName, $index)
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($index) {
                $table->dropUnique($index);
            });
        } catch (\Exception $e) {
            // Index likely does not exist; continue safely.
        }
    }
};