<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->string('registration_no', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->enum('gender', ['male','female','other']);
            $table->date('date_of_birth');
            $table->string('national_id', 50)->nullable();
            $table->string('nfc_tag_uid', 100)->nullable();
            $table->string('qr_code_token', 100)->nullable()->unique();
            $table->enum('status', ['active','transferred','withdrawn','graduated'])->default('active');
            $table->timestamps();

//            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->unique(['institute_id','registration_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
