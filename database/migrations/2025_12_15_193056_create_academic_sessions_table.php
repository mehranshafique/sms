<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            
            // Link to Institution
            $table->foreignId('institution_id')
                  ->constrained('institutions')
                  ->cascadeOnDelete();

            // Session Name (e.g., "2024-2025" or "Spring 2025")
            $table->string('name', 50);

            // Use DATE type for flexibility (Best Practice)
            $table->date('start_date');
            $table->date('end_date');

            // Status Enum
            $table->enum('status', ['planned', 'active', 'closed'])
                  ->default('planned');

            // Flag for current session (Only one true per institution)
            $table->boolean('is_current')->default(false);

            $table->timestamps();

            // Constraints
            // 1. Ensure name is unique per institution
            $table->unique(['institution_id', 'name']);
            
            // 2. Ideally, date ranges shouldn't overlap for the same institution, 
            // but that's complex to enforce in SQL. Handled in Controller validation.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_sessions');
    }
};