<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Disable checks temporarily
        Schema::disableForeignKeyConstraints();

        Schema::table('student_attendances', function (Blueprint $table) {
            // 2. Drop the old strict daily limit
            $table->dropUnique('student_daily_attendance_unique');
            
            // 3. Add the new flexible limit (Student + Date + Subject)
            $table->unique(['student_id', 'attendance_date', 'subject_id'], 'student_subject_attendance_unique');
        });

        // 4. Re-enable checks
        Schema::enableForeignKeyConstraints();
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropUnique('student_subject_attendance_unique');
            $table->unique(['student_id', 'attendance_date'], 'student_daily_attendance_unique');
        });

        Schema::enableForeignKeyConstraints();
    }
};