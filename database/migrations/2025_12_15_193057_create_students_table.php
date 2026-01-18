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
            
            // --- 1. RELATIONSHIPS & AUTH ---
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('parents')->onDelete('set null');
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            
            // --- 2. ACADEMIC INFO ---
            $table->unsignedBigInteger('grade_level_id')->nullable(); 
            $table->unsignedBigInteger('class_section_id')->nullable();
            
            // --- 3. IDENTITY ---
            $table->string('admission_number', 50)->unique();
            $table->string('roll_number', 20)->nullable();
            $table->date('admission_date');

            // --- 4. PERSONAL INFO ---
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('post_name')->nullable();
            $table->string('gender', 10);
            $table->date('dob');
            $table->string('place_of_birth')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('category', 50)->nullable();
            
            // --- 5. CONTACT & LOCATION ---
            $table->string('mobile_number', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('avenue')->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            
            $table->string('primary_guardian')->default('father');

            // --- 6. MEDIA & ACCESS ---
            $table->string('student_photo')->nullable();
            $table->string('qr_code_token', 100)->nullable()->unique();
            $table->string('nfc_tag_uid', 100)->nullable()->unique();

            // --- 7. STATUS & FINANCE ---
            $table->enum('status', ['active', 'inactive', 'transferred', 'suspended', 'graduated'])->default('active');
            $table->enum('payment_mode', ['global', 'installment'])->default('installment');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};