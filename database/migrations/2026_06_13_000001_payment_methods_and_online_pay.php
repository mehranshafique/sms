<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_token')) {
                $table->string('payment_token', 64)->nullable()->unique()->after('status');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'source')) {
                $table->string('source', 20)->default('admin')->after('method');
            }
            if (!Schema::hasColumn('payments', 'payer_name')) {
                $table->string('payer_name', 100)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('payments', 'payer_phone')) {
                $table->string('payer_phone', 30)->nullable()->after('payer_name');
            }
        });

        if (Schema::hasColumn('payments', 'method')) {
            DB::statement("ALTER TABLE payments MODIFY method VARCHAR(50) NOT NULL DEFAULT 'cash'");
        }

        $invoiceIds = DB::table('invoices')->whereNull('payment_token')->pluck('id');
        foreach ($invoiceIds as $id) {
            DB::table('invoices')->where('id', $id)->update([
                'payment_token' => Str::random(48),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'payment_token')) {
                $table->dropColumn('payment_token');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            foreach (['source', 'payer_name', 'payer_phone'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
