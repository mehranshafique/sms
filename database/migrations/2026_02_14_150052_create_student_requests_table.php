<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            
            // Core Data
            $table->string('type'); // absence, late, sick, early_exit
            $table->text('reason')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // Meta
            $table->string('ticket_number')->unique();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Can be Student or Admin
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};