<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'half_day'])->default('absent');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('method', ['manual', 'qr', 'nfc', 'biometric'])->default('manual');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Unique constraint to prevent duplicate records per day per staff
            $table->unique(['staff_id', 'attendance_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_attendances');
    }
};