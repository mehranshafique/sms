<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Default to 'installment' as it's the common case, or 'global' depending on preference
            $table->enum('payment_mode', ['global', 'installment'])->default('installment')->after('status');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('payment_mode');
        });
    }
};