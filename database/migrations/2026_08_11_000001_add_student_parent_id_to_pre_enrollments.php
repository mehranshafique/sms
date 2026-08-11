<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('pre_enrollments', 'student_parent_id')) {
                $table->foreignId('student_parent_id')
                    ->nullable()
                    ->after('parent_email')
                    ->constrained('parents')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pre_enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('pre_enrollments', 'student_parent_id')) {
                $table->dropConstrainedForeignId('student_parent_id');
            }
        });
    }
};
