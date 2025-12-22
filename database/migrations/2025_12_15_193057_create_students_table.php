<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            
            // Link to User Table (Auth)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Core Links
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            
            // Academic Info
            $table->unsignedBigInteger('grade_level_id')->nullable(); 
            $table->unsignedBigInteger('class_section_id')->nullable();
            
            // Identity
            $table->string('admission_number', 50)->unique();
            $table->string('roll_number', 20)->nullable();
            $table->date('admission_date');

            // Personal Info
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('gender', 10);
            $table->date('dob');
            $table->string('blood_group', 5)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('category', 50)->nullable();
            
            // Contact
            $table->string('mobile_number', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            
            // Parents / Guardian
            $table->string('father_name', 100)->nullable();
            $table->string('father_phone', 20)->nullable();
            $table->string('father_occupation', 100)->nullable();
            
            $table->string('mother_name', 100)->nullable();
            $table->string('mother_phone', 20)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            
            $table->string('guardian_name', 100)->nullable();
            $table->string('guardian_relation', 50)->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_email', 100)->nullable();

            // Media & Access
            $table->string('student_photo')->nullable();
            
            // NEW FIELDS
            $table->string('qr_code_token', 100)->nullable()->unique();
            $table->string('nfc_tag_uid', 100)->nullable()->unique();

            // Status
            $table->enum('status', ['active', 'inactive', 'transferred', 'suspended', 'graduated'])->default('active');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};