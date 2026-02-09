<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Keywords Config
        Schema::create('chatbot_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->cascadeOnDelete(); // Null = Global Keyword
            $table->string('keyword')->index(); // e.g. "bonjour", "hello"
            $table->string('language')->default('en'); // 'en', 'fr'
            $table->text('welcome_message')->nullable(); // Custom welcome reply
            $table->timestamps();
        });

        // 2. Active Chat Sessions (Replaces legacy 'chatbots' table logic)
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->index(); // The user's WhatsApp number
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete(); // Identified School
            
            // Auth Data
            $table->string('identifier_input')->nullable(); // The ID they entered (e.g. ADM-001)
            $table->nullableMorphs('user'); // Links to Student, Staff, or User model
            
            // Security
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->integer('attempts')->default(0);
            
            // State Machine
            $table->string('status')->default('INIT'); // INIT, AWAITING_ID, AWAITING_OTP, ACTIVE
            $table->string('locale', 5)->default('en')->comment('en or fr');
            $table->timestamp('last_interaction_at')->nullable(); // For session timeout logic
            $table->timestamp('expires_at')->nullable(); // Session timeout
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('chatbot_keywords');
    }
};