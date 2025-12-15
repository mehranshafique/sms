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
        Schema::create('campuses', function (Blueprint $table) {
            $table->id(); // PK
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            
            $table->string('name', 150);
            $table->string('code', 30); // Unique per institution handled via compound unique index below
            
            // Location & Contact
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Constraint: Campus codes must be unique WITHIN an institution
            $table->unique(['institution_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campuses');
    }
};
