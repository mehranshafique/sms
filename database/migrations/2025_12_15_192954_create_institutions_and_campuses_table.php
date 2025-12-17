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
        // 1. Institutions (The Legal Entity / Network)
        if (!Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->id(); // PK, bigint. This is the "InstitutionID" prefix.
                $table->string('name', 150);
                $table->string('code', 30)->unique();
                $table->enum('type', ['primary', 'secondary', 'university', 'mixed']);
                
                // Location & Contact
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->text('address')->nullable(); // changed to text for flexibility
                $table->string('phone', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('logo')->nullable(); 
                
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Campuses (Physical Branches)
        if (!Schema::hasTable('campuses')) {
            Schema::create('campuses', function (Blueprint $table) {
                $table->id(); // PK
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                
                $table->string('name', 150);
                $table->string('code', 30); // Unique per institution via compound index
                
                // Location & Contact
                $table->text('address')->nullable();
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campuses');
        Schema::dropIfExists('institutions');
    }
};