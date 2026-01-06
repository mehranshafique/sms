<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            // Adding discount fields
            $table->decimal('discount_amount', 10, 2)->default(0)->after('status');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed')->after('discount_amount');
            $table->string('scholarship_reason')->nullable()->after('discount_type');
        });
    }

    public function down()
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_type', 'scholarship_reason']);
        });
    }
};