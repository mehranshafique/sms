<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->time('check_in_time');
            $table->time('check_out_time')->nullable();
            $table->unsignedSmallInteger('late_margin_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institution_id', 'name']);
        });

        Schema::create('attendance_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_schedule_id')
                ->constrained('attendance_schedules')
                ->cascadeOnDelete();
            $table->foreignId('grade_level_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('class_section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique('grade_level_id');
            $table->unique('class_section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_schedule_assignments');
        Schema::dropIfExists('attendance_schedules');
    }
};
