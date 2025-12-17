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
            // Direct link for simple users (Staff/Student) if needed, usually managed via profile tables
            // but can be kept nullable here for quick lookups.
            if (!Schema::hasColumn('users', 'institute_id')) {
                 $table->foreignId('institute_id')->nullable()->constrained('institutions')->onDelete('set null')->after('id');
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
            $table->dropForeign(['institute_id']);
            $table->dropColumn(['institute_id', 'phone', 'address', 'user_type', 'is_active', 'language']);
        });
    }
};