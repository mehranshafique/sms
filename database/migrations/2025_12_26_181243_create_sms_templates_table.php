<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->nullable()->index(); // Null for System/Global Templates
            $table->string('event_key'); // e.g., 'institution_creation', 'payment_received'
            $table->string('name'); // Human readable name
            $table->text('body'); // The message with variables like $Name
            $table->text('available_tags')->nullable(); // JSON or CSV of tags user can use
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: One event per institution (or one global event)
            $table->unique(['institution_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};