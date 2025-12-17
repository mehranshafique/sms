<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            
            // Core Links
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            
            // Link to Grade Level (A subject belongs to a specific grade's curriculum)
            $table->foreignId('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();

            // Details
            $table->string('name', 100); // e.g. "Mathematics"
            $table->string('code', 30)->nullable(); // e.g. "MATH-01"
            $table->enum('type', ['theory', 'practical', 'both'])->default('theory');
            
            $table->integer('credit_hours')->nullable()->default(0);
            $table->integer('total_marks')->default(100);
            $table->integer('passing_marks')->default(40);
            
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Constraint: Subject Name should be unique within a grade level
            $table->unique(['grade_level_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};