<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Institutions (The Network)
        if (!Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->id(); 
                $table->string('name', 150);
                $table->string('code', 30)->unique();
                $table->enum('type', ['primary', 'secondary', 'university', 'mixed']);
                
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('logo')->nullable(); 
                $table->integer('sms_credits')->default(0);
                
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Campuses (Branches)
        if (!Schema::hasTable('campuses')) {
            Schema::create('campuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                
                $table->string('name', 150);
                $table->string('code', 30);
                
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('country')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email', 150)->nullable();
                
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['institution_id', 'code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campuses');
        Schema::dropIfExists('institutions');
    }
};