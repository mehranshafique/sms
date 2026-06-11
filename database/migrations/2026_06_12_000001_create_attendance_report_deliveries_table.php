<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance_report_deliveries')) {
            Schema::create('attendance_report_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->enum('period_type', ['week', 'month']);
                $table->date('period_start');
                $table->date('period_end');
                $table->string('channel', 20)->default('sms');
                $table->timestamp('sent_at');
                $table->timestamps();
                $table->unique(['student_id', 'period_type', 'period_start'], 'att_report_deliv_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_report_deliveries');
    }
};
