<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();

//            $table->foreignId('user_id')
//                ->unique()
//                ->constrained('users')
//                ->onDelete('cascade');

//            $table->foreignId('campus_id')
//                ->constrained('campuses')
//                ->onDelete('cascade');
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('users_id');
            $table->string('employee_no', 50); // UNIQUE per campus
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');

            $table->timestamps();

            $table->unique(['institute_id', 'employee_no']); // unique per campus
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff');
    }
};
