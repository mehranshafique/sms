<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sections', function (Blueprint $table) {
            $table->id();
            
            // Core Links
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            $table->foreignId('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
            
            // Class Teacher (Optional initially)
            // Note: Staff table uses 'id' as PK, so this is correct.
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');

            // Details
            $table->string('name', 100); // e.g., "Section A", "Blue", "Science Group"
            $table->string('code', 30)->nullable();
            $table->string('room_number', 50)->nullable();
            $table->integer('capacity')->default(40);
            
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Constraints: Name should be unique within a grade level (e.g. Can't have two "Section A" in Grade 1)
            $table->unique(['grade_level_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sections');
    }
};