<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Exams Table (The Event)
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            
            $table->string('name', 100); // e.g. "Mid-Term Exam 2025"
            $table->date('start_date');
            $table->date('end_date');
            
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'published'])->default('scheduled');
            $table->text('description')->nullable();

            $table->timestamps();
        });

        // 2. Exam Records Table (The Marks)
        Schema::create('exam_records', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete(); // Snapshot of where student was
            
            // Marks
            $table->decimal('marks_obtained', 5, 2)->default(0);
            $table->boolean('is_absent')->default(false);
            $table->string('remarks')->nullable();
            
            $table->timestamps();

            // Unique constraint: One mark per student per subject per exam
            $table->unique(['exam_id', 'student_id', 'subject_id'], 'unique_exam_mark');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_records');
        Schema::dropIfExists('exams');
    }
};