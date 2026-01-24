<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->string('period_name')->nullable()->after('budget_category_id')->comment('e.g. Q1, January, First Trimester');
            $table->date('start_date')->nullable()->after('period_name');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn(['period_name', 'start_date', 'end_date']);
        });
    }
};