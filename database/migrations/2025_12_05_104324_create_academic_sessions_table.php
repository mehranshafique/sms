<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institution_id');

            $table->string('name', 50);                      // Example: "2025-2026"
            $table->integer('start_year');                  // Example: 2025
            $table->integer('end_year');                    // Example: 2026

            $table->boolean('is_current')->default(false);  // Only one allowed per institute
            $table->enum('status', ['planned', 'active', 'closed'])
                ->default('planned');

            $table->timestamps();

//            $table->foreign('institution_id')
//                ->references('id')
//                ->on('institutions')
//                ->onDelete('cascade');

//            $table->unique(['institution_id', 'is_current'], 'unique_current_session')
//                ->where('is_current', true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_sessions');
    }
};
