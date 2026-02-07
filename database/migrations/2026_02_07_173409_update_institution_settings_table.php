<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            // Make institution_id nullable to support Global/Super Admin settings
            // We also need to drop the foreign key first if it exists to modify the column
            // Assuming standard FK name or just modifying column
            
            // Check if foreign key exists (Laravel default naming convention)
            try {
                $table->dropForeign(['institution_id']); 
            } catch (\Exception $e) {
                // Ignore if FK doesn't exist
            }

            $table->unsignedBigInteger('institution_id')->nullable()->change();

            // Re-add foreign key with null on delete
            $table->foreign('institution_id')
                  ->references('id')
                  ->on('institutions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Warning: This down migration might fail if there are null records
        Schema::table('institution_settings', function (Blueprint $table) {
             // It's risky to revert nullable to not null without data cleanup
             // We leave it nullable for safety in down direction or implement cleanup logic
        });
    }
};