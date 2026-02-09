<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            
            // Security
            $table->string('token')->unique(); // The value inside the QR
            $table->string('otp')->nullable(); // OTP used to verify generation
            
            // Status Tracking
            $table->enum('status', ['pending', 'scanned', 'approved', 'rejected', 'expired'])->default('pending');
            
            // Actors
            $table->string('requested_by')->nullable()->comment('Name of person picking up');
            
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete(); // Guard
            $table->timestamp('scanned_at')->nullable();
            
            // Added for Teacher Approval Workflow
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Teacher/Admin
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamp('expires_at')->nullable()->comment('When the QR code expires');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_pickups');
    }
};