<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->string('temporary_id', 64);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('post_name')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('dob')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 40)->nullable();
            $table->string('parent_email')->nullable();
            $table->foreignId('requested_grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
            $table->foreignId('requested_class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->string('requested_option')->nullable();
            $table->string('status', 40)->default('pre_enrolled')->index();
            $table->dateTime('test_at')->nullable();
            $table->string('test_location')->nullable();
            $table->text('test_notes')->nullable();
            $table->decimal('test_score', 8, 2)->nullable();
            $table->string('test_result', 20)->nullable(); // pass|fail|null
            $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('source', 32)->default('admin'); // admin|web|whatsapp
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'temporary_id'], 'pre_enroll_inst_temp_uq');
            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_enrollments');
    }
};
