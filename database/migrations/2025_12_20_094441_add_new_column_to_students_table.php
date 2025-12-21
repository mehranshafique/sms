<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('post_name')->nullable()->after('last_name');
            $table->string('place_of_birth')->nullable()->after('dob');
            $table->string('province')->nullable()->after('email');
            $table->string('avenue')->nullable()->after('province'); // Address detail
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            $table->string('primary_guardian')->default('father')->after('mother_name'); // father, mother, guardian
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['post_name', 'place_of_birth', 'province', 'avenue', 'primary_guardian']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};