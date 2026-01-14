<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to support the new Modular Evaluation Framework.
     */
    public function up(): void
    {
        // 1. Add category to Exams (P1, P2... Trimester Exam)
        Schema::table('exams', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('exams', 'category')) {
                $blueprint->string('category', 50)->nullable()->after('name');
            }
        });

        // 2. Ensure Grade Levels have the education cycle
        Schema::table('grade_levels', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('grade_levels', 'education_cycle')) {
                $blueprint->string('education_cycle', 50)->default('primary')->after('order_index');
            }
        });

        // 3. Ensure Subjects have credit hours (Volume Horaire) for LMD
        Schema::table('subjects', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('subjects', 'credit_hours')) {
                $blueprint->integer('credit_hours')->default(0)->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $blueprint) {
            $blueprint->dropColumn('category');
        });

        Schema::table('grade_levels', function (Blueprint $blueprint) {
            $blueprint->dropColumn('education_cycle');
        });

        Schema::table('subjects', function (Blueprint $blueprint) {
            $blueprint->dropColumn('credit_hours');
        });
    }
};