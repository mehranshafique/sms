<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_payment_periods', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('agent_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_payment_period_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_payments');
        Schema::dropIfExists('agent_payment_periods');
    }
};
