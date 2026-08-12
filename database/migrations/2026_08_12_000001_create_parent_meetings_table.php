<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('topic');
            $table->date('preferred_date');
            $table->text('notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['student_id', 'preferred_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_meetings');
    }
};
