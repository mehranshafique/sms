<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
             $table->time('check_in')->nullable()->after('marked_by');
            $table->time('check_out')->nullable()->after('check_in');
            $table->enum('method', ['manual', 'qr', 'nfc','rfid', 'biometric'])->default('manual')->after('check_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in', 'check_out', 'method']);
        });
    }
};
