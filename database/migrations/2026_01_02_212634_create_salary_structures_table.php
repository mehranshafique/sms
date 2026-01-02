<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            // Basic Salary Components
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('hourly_rate', 8, 2)->nullable(); // For hourly staff
            
            // Allowances (Stored as JSON for flexibility or separate columns)
            // JSON: {"transport": 50, "housing": 100}
            $table->json('allowances')->nullable(); 
            
            // Deductions (JSON: {"tax": 5%, "insurance": 20})
            $table->json('deductions')->nullable();
            
            $table->enum('payment_basis', ['monthly', 'hourly'])->default('monthly');
            
            $table->timestamps();
        });

        // Payroll Records Table (Payslips)
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            $table->date('month_year'); // e.g., 2025-01-01 for Jan 2025
            
            // Attendance Summary
            $table->integer('total_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('late_days')->default(0);
            
            // Financials
            $table->decimal('basic_pay', 12, 2);
            $table->decimal('total_allowance', 12, 2)->default(0);
            $table->decimal('total_deduction', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2);
            
            $table->enum('status', ['generated', 'paid'])->default('generated');
            $table->date('paid_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_structures');
    }
};