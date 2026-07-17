<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->nullable()->index();
            $table->string('channel', 16)->index(); // sms|whatsapp
            $table->string('event_key', 64)->nullable()->index();
            $table->string('to_masked', 24); // e.g. ****5678 — avoids full PII bulk
            $table->string('status', 16)->index(); // sent|failed|skipped
            $table->string('provider', 32)->nullable();
            $table->string('provider_msg_id', 64)->nullable();
            $table->string('error', 191)->nullable(); // short failure reason only
            $table->boolean('credited')->default(false);
            $table->nullableMorphs('related'); // related_type + related_id
            $table->timestamp('created_at')->useCurrent()->index();

            // No updated_at — append-only log keeps rows small
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
