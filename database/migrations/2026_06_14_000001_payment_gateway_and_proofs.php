<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('gateway', 30);
            $table->string('external_id', 64)->unique();
            $table->string('gateway_reference', 120)->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('CDF');
            $table->string('method', 50)->nullable();
            $table->string('payer_name', 100)->nullable();
            $table->string('payer_phone', 30)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('checkout_url', 500)->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_proof_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('payer_name', 100);
            $table->string('payer_phone', 30);
            $table->string('method', 50);
            $table->decimal('amount', 12, 2);
            $table->dateTime('paid_at');
            $table->string('transaction_reference', 120);
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proof_submissions');
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
