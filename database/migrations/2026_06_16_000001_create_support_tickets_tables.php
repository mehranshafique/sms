<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 30)->nullable()->unique();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // requester
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // support agent
            $table->string('subject');
            $table->string('category', 40)->default('general');
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->string('status', 20)->default('open');      // open, pending, answered, resolved, closed
            $table->timestamp('last_reply_at')->nullable();
            $table->foreignId('last_reply_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('user_last_read_at')->nullable();
            $table->timestamp('support_last_read_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('institution_id');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_support')->default(false); // authored by Digitex support team
            $table->boolean('is_system')->default(false);  // automated status note
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();

            $table->index('support_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
