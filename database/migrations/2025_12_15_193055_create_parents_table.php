<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            
            // Link to User Table (For Parent Login)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Institution Scope
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();

            // Father Details
            $table->string('father_name', 100)->nullable();
            $table->string('father_phone', 20)->nullable();
            $table->string('father_occupation', 100)->nullable();
            
            // Mother Details
            $table->string('mother_name', 100)->nullable();
            $table->string('mother_phone', 20)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            
            // Guardian Details
            $table->string('guardian_name', 100)->nullable();
            $table->string('guardian_relation', 50)->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_email', 100)->nullable();
            
            // Address (Shared Family Address)
            $table->text('family_address')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};