<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_medical_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();

            // Mirrors students.blood_group so the infirmary keeps one source of truth.
            $table->string('blood_group', 10)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('current_medication')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('doctor_name', 150)->nullable();
            $table->string('doctor_phone', 30)->nullable();
            $table->string('insurance_provider', 150)->nullable();
            $table->string('insurance_number', 100)->nullable();

            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_relation', 100)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_alt_phone', 30)->nullable();

            $table->boolean('consent_first_aid')->default(true);
            $table->date('information_date')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'student_id']);
        });

        Schema::create('infirmary_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();

            $table->dateTime('visited_at');
            $table->string('reason', 255);
            $table->text('observation')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('temperature', 10)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->enum('outcome', ['returned_to_class', 'rested', 'sent_home', 'referred_hospital', 'other'])
                ->default('returned_to_class');
            $table->boolean('parent_informed')->default(false);
            $table->dateTime('parent_informed_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'student_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infirmary_visits');
        Schema::dropIfExists('student_medical_profiles');
    }
};
