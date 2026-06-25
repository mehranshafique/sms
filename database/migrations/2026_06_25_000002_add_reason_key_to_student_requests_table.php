<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->string('reason_key')->nullable()->after('reason');
            $table->json('reason_params')->nullable()->after('reason_key');
            $table->string('reason_locale', 5)->nullable()->after('reason_params');
        });
    }

    public function down(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->dropColumn(['reason_key', 'reason_params', 'reason_locale']);
        });
    }
};
