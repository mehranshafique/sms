<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('student_attendances', 'parents_notified_at')) {
                $table->timestamp('parents_notified_at')->nullable()->after('method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('student_attendances', 'parents_notified_at')) {
                $table->dropColumn('parents_notified_at');
            }
        });
    }
};
