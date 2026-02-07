<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Academic Units (UE) Table
        Schema::create('academic_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_level_id')->constrained()->cascadeOnDelete(); // Linked to Level (L1, L2...)
            
            $table->string('name'); // e.g., UE Fondamentale
            $table->string('code')->nullable(); // e.g., UEF-101
            $table->enum('type', ['fundamental', 'transversal', 'optional'])->default('fundamental');
            $table->integer('semester')->default(1); // 1 or 2
            $table->decimal('total_credits', 5, 2)->default(0); // Sum of child subject credits
            
            $table->timestamps();
        });

        // 2. Link Subjects to UE
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('academic_unit_id')
                  ->nullable()
                  ->after('grade_level_id')
                  ->constrained('academic_units')
                  ->nullOnDelete();
                  
            // Ensure coefficient exists for weighted avg calculation within UE
            if (!Schema::hasColumn('subjects', 'coefficient')) {
                $table->decimal('coefficient', 4, 1)->default(1)->after('credit_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['academic_unit_id']);
            $table->dropColumn(['academic_unit_id', 'coefficient']);
        });
        Schema::dropIfExists('academic_units');
    }
};