<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->id();
                
                // Link to User (Authentication)
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                
                // Link to Organization
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');

                // Professional Details
                $table->string('employee_id', 50)->nullable()->unique(); // Generated ID
                $table->string('designation', 100)->nullable();
                $table->string('department', 100)->nullable();
                $table->date('joining_date')->nullable();
                $table->decimal('salary', 10, 2)->nullable();
                
                // Personal Details (Some overlap with User, but specific to HR record)
                $table->string('gender', 10)->nullable();
                $table->date('dob')->nullable();
                $table->string('qualification', 150)->nullable();
                $table->string('experience', 50)->nullable(); // e.g. "5 Years"
                
                // Contact
                $table->string('emergency_contact', 20)->nullable();
                $table->text('address')->nullable();

                // Status
                $table->enum('status', ['active', 'on_leave', 'resigned', 'terminated'])->default('active');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};