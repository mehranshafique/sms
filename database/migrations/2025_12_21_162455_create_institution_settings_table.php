<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // To categorize settings (attendance, exams, etc.)
            $table->timestamps();

            // Ensure unique key per institution
            $table->unique(['institution_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_settings');
    }
};