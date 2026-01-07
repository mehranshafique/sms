<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            // Check if column exists before adding to prevent errors on re-runs
            if (!Schema::hasColumn('roles', 'institution_id')) {
                $table->unsignedBigInteger('institution_id')->nullable()->after('id');
                $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
                
                // Standard Spatie roles table has a unique index on ['name', 'guard_name']
                // We wrap this in a check/try to ensure it drops correctly
                try {
                    $table->dropUnique(['name', 'guard_name']);
                } catch (\Exception $e) {
                    // Index might not exist or have a custom name; ignore and continue
                }

                // Add the new scoped unique index
                $table->unique(['name', 'guard_name', 'institution_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            // Check if column exists before attempting to drop it or its keys
            if (Schema::hasColumn('roles', 'institution_id')) {
                
                // 1. Drop the scoped unique index first
                try {
                    $table->dropUnique(['name', 'guard_name', 'institution_id']);
                } catch (\Exception $e) {}

                // 2. Drop the foreign key constraint
                $table->dropForeign(['institution_id']);
                
                // 3. Drop the column
                $table->dropColumn('institution_id');

                // 4. Restore the original unique constraint
                try {
                    $table->unique(['name', 'guard_name']);
                } catch (\Exception $e) {}
            }
        });
    }
};