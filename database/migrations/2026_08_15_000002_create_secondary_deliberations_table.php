<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secondary_deliberations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->json('failed_subjects')->nullable();
            $table->decimal('average_percentage', 6, 2)->nullable();
            $table->enum('decision', ['pending', 'admitted', 'repechage', 'adjourned'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['academic_session_id', 'student_id'],
                'secondary_deliberations_session_student_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secondary_deliberations');
    }
};
