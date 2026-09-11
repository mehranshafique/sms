<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subject_grade_level')) {
            Schema::create('subject_grade_level', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['subject_id', 'grade_level_id']);
            });
        }

        $rows = DB::table('subjects')
            ->whereNotNull('grade_level_id')
            ->select('id', 'grade_level_id', 'created_at', 'updated_at')
            ->get();

        $now = now();
        foreach ($rows as $row) {
            $exists = DB::table('subject_grade_level')
                ->where('subject_id', $row->id)
                ->where('grade_level_id', $row->grade_level_id)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('subject_grade_level')->insert([
                'subject_id' => $row->id,
                'grade_level_id' => $row->grade_level_id,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);
        }

        // Drop FK if present (may already be dropped from a partial run)
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropForeign(['grade_level_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // Drop unique index used by legacy name-per-grade constraint
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropUnique(['grade_level_id', 'name']);
            });
        } catch (\Throwable $e) {
            try {
                DB::statement('ALTER TABLE subjects DROP INDEX subjects_grade_level_id_name_unique');
            } catch (\Throwable $e2) {
                // ignore if already dropped
            }
        }

        DB::statement('ALTER TABLE subjects MODIFY grade_level_id BIGINT UNSIGNED NULL');

        // Re-add FK as nullable
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreign('grade_level_id')
                    ->references('id')
                    ->on('grade_levels')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // ignore if already exists
        }
    }

    public function down(): void
    {
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropForeign(['grade_level_id']);
            });
        } catch (\Throwable $e) {
        }

        DB::statement('ALTER TABLE subjects MODIFY grade_level_id BIGINT UNSIGNED NOT NULL');

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('grade_level_id')
                ->references('id')
                ->on('grade_levels')
                ->cascadeOnDelete();
            $table->unique(['grade_level_id', 'name']);
        });

        Schema::dropIfExists('subject_grade_level');
    }
};
