<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sms', 'whatsapp']);
            $table->integer('amount');
            $table->unsignedInteger('balance_before')->default(0);
            $table->unsignedInteger('balance_after')->default(0);
            $table->string('action', 32)->default('recharge');
            $table->string('status', 16)->default('active');
            $table->text('note')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reverses_transaction_id')->nullable()->constrained('institution_credit_transactions')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'type', 'created_at'], 'ict_inst_type_created_idx');
            $table->index(['institution_id', 'status'], 'ict_inst_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_credit_transactions');
    }
};
