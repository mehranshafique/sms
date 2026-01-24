<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            
            // Optional: The main teacher for this subject in this class
            $table->foreignId('teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            
            // Client Requirements
            $table->integer('weekly_periods')->default(0)->comment('Max periods per week');
            $table->decimal('exam_weight', 5, 2)->default(100.00)->comment('Weight in percentage');
            
            $table->timestamps();

            // Unique constraint: A subject can only be assigned once per class per session
            $table->unique(['academic_session_id', 'class_section_id', 'subject_id'], 'class_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_subjects');
    }
};