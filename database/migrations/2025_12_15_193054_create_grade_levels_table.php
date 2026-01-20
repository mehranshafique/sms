<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('institution_id')
                  ->constrained('institutions')
                  ->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('code', 30)->nullable();
            
            // Used for sorting (e.g. 1 for Grade 1, 13 for University Year 1)
            $table->unsignedSmallInteger('order_index')->default(0); 
            
            $table->enum('education_cycle', ['primary', 'secondary', 'university', 'vocational'])
                  ->default('primary');

            $table->timestamps();

            // FIXED: Unique name per institution AND education cycle
            // This allows "Grade 1" in Primary and "Grade 1" in Secondary to exist simultaneously
            $table->unique(['institution_id', 'name', 'education_cycle']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};