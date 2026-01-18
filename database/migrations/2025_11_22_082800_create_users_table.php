<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Consolidates all user-related schema changes.
     * References 'institutions' table (must be created first).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Fixed reference to 'institutions' table
            $table->foreignId('institute_id')->nullable()->constrained('institutions')->onDelete('set null');
            
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            
            $table->string('password');
            
            // User Type: 1:SuperAdmin, 2:HeadOfficer, 3:BranchAdmin, 4:Staff, 5:Student, 6:Parent
            $table->tinyInteger('user_type')->default(4);
            $table->boolean('is_active')->default(true);
            $table->string('profile_picture')->nullable();
            $table->string('language', 10)->default('en');
            
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Pivot Table for Head Officers
        Schema::create('institution_head_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['institution_id', 'user_id'], 'inst_head_officer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_head_officers');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};