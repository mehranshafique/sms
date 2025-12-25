<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Packages (Plans)
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Basic, Pro, Enterprise
            $table->decimal('price', 10, 2);
            $table->integer('duration_days')->default(365); // 30, 365
            $table->json('modules')->nullable(); // ["academics", "finance"]
            $table->integer('student_limit')->nullable();
            $table->integer('staff_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('trial_ends_at')->nullable();
            $table->string('status')->default('active'); // active, expired, cancelled, pending_payment
            $table->decimal('price_paid', 10, 2);
            $table->string('payment_method')->nullable(); // manual, stripe, bank_transfer
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Platform Invoices (Billing to Schools)
        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('unpaid'); // unpaid, paid, overdue, cancelled
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('packages');
    }
};