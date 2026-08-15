<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('call_id')->unique();
            $table->string('phone_number', 32)->index();
            $table->string('to_number', 32)->nullable();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('locale', 5)->default('fr');
            $table->string('state', 40)->default('WELCOME')->index();
            $table->string('menu_profile', 20)->default('guest'); // guest|parent
            $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('last_digit', 8)->nullable();
            $table->unsignedSmallInteger('turns')->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_sessions');
    }
};
