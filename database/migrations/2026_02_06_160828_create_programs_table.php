<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Programs Table
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->integer('total_semesters')->default(8);
            $table->integer('duration_years')->default(4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Update GradeLevels to link to Programs
        Schema::table('grade_levels', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('institution_id')->constrained()->nullOnDelete();
        });

        // 3. Update Academic Units (The Fix)
        Schema::table('academic_units', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('institution_id')->constrained()->nullOnDelete();
            
            // CRITICAL FIX: Make grade_level_id nullable because a Unit might belong to a Program globally
            // or specific semester logic handled by the service.
            $table->unsignedBigInteger('grade_level_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('academic_units', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
            // Revert grade_level_id to not null is risky if data exists, skipping strict revert
        });
        Schema::table('grade_levels', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
        });
        Schema::dropIfExists('programs');
    }
};