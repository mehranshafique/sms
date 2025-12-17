<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fee Types (e.g. Tuition, Transport, Library)
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Fee Structures (Linking Fee Types to amounts and frequency)
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained('fee_types')->cascadeOnDelete();
            
            // Applicable To (Optional filters)
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->onDelete('cascade');
            
            $table->string('name', 100); // e.g. "Grade 1 Tuition Fee 2025"
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['one_time', 'monthly', 'termly', 'yearly'])->default('termly');
            
            $table->timestamps();
        });

        // 3. Invoices (Assigned fees to students)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            $table->string('invoice_number', 50)->unique();
            $table->date('issue_date');
            $table->date('due_date');
            
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            
            $table->timestamps();
        });

        // 4. Invoice Items (Line items for each invoice)
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->onDelete('set null');
            
            $table->string('description', 255);
            $table->decimal('amount', 10, 2);
            
            $table->timestamps();
        });

        // 5. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            
            $table->string('transaction_id', 100)->unique()->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'bank_transfer', 'card', 'online'])->default('cash');
            $table->text('notes')->nullable();
            
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_types');
    }
};