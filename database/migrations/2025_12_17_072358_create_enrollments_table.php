<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            
            // Core Links
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            // Academic Placement
            $table->foreignId('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();

            // Session Specific Details
            $table->string('roll_number', 20)->nullable(); // e.g. "101"
            
            // Enrollment Status
            $table->enum('status', ['active', 'promoted', 'detained', 'left', 'graduated'])->default('active');
            
            $table->date('enrolled_at')->nullable();
            
            $table->timestamps();

            // Constraint: A student can only be enrolled once per session
            $table->unique(['academic_session_id', 'student_id'], 'unique_session_student');
            
            // Constraint: Roll number unique per section per session
            $table->unique(['academic_session_id', 'class_section_id', 'roll_number'], 'unique_section_roll');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};