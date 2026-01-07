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
        // 1. Update Users Table with new fields required by the system
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            // User Type: 1:SuperAdmin, 2:HeadOfficer, 3:BranchAdmin, 4:Staff, 5:Student, 6:Guardian
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->tinyInteger('user_type')->default(4)->after('password');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('user_type');
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language', 10)->default('en')->after('is_active');
            }
            // Direct link for simple users (Staff/Student) if needed
            if (!Schema::hasColumn('users', 'institute_id')) {
                 $table->unsignedBigInteger('institute_id')->nullable()->after('id');
                 $table->foreign('institute_id')->references('id')->on('institutions')->onDelete('set null');
            }
        });

        // 2. Create Head Officer Assignments Pivot Table (Many-to-Many)
        if (!Schema::hasTable('institution_head_officers')) {
            Schema::create('institution_head_officers', function (Blueprint $table) {
                $table->id();
                
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

                $table->timestamps();

                // Prevent duplicate assignments
                $table->unique(['institution_id', 'user_id'], 'inst_head_officer_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_head_officers');
        
        Schema::table('users', function (Blueprint $table) {
            // Check if column exists before attempting to drop it or its foreign key
            if (Schema::hasColumn('users', 'institute_id')) {
                // Safely attempt to drop foreign key
                try {
                    $table->dropForeign(['institute_id']);
                } catch (\Exception $e) {
                    // Key might have been created with a different name or already dropped
                }
                
                $table->dropColumn('institute_id');
            }

            // Clean up remaining columns if they exist
            $columnsToDrop = ['phone', 'address', 'user_type', 'is_active', 'language'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};