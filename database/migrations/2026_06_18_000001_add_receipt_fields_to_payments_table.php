<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_number', 32)->nullable()->after('transaction_id');
            $table->string('receipt_verify_token', 64)->nullable()->unique()->after('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['receipt_number', 'receipt_verify_token']);
        });
    }
};
