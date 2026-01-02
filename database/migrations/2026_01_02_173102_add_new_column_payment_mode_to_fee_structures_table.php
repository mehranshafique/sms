<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->enum('payment_mode', ['global', 'installment'])->default('global')->after('amount');
            $table->integer('installment_order')->nullable()->after('payment_mode')->comment('Sequence order: 1, 2, 3...');
        });
    }

    public function down()
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn(['payment_mode', 'installment_order']);
        });
    }
};