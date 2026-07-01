<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->date('payment_deadline')->nullable()->after('end_date');
        });

        DB::table('student_requests')->where('status', 'pending')->update(['status' => 'submitted']);
    }

    public function down(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->dropColumn('payment_deadline');
        });

        DB::table('student_requests')->where('status', 'submitted')->update(['status' => 'pending']);
    }
};
