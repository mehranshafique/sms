<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_class_subject_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->boolean('is_examined')->default(true);
            $table->timestamps();

            $table->unique(['exam_id', 'class_section_id', 'subject_id'], 'exam_class_subject_setting_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_class_subject_settings');
    }
};
