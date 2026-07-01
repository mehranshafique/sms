<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('contact')->nullable();
            $table->string('audience')->default('parents');
            $table->json('class_section_ids')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('school_event_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_event_invitations');
        Schema::dropIfExists('school_events');
    }
};
