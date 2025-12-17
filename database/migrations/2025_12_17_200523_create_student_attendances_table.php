<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            
            // Core Context
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            
            // The Student
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            // Attendance Details
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'half_day'])->default('present');
            $table->string('remarks', 255)->nullable();
            
            // Who marked it? (Optional, good for auditing)
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Prevent duplicate records for the same student on the same day
            $table->unique(['student_id', 'attendance_date'], 'student_daily_attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};