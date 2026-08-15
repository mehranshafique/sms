<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // 'none' keeps the historical behaviour; 'proportional' means the fee is
            // broken into components and every payment is split across them.
            $table->enum('allocation_mode', ['none', 'proportional'])
                ->default('none')
                ->after('installment_order');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn('allocation_mode');
        });
    }
};
