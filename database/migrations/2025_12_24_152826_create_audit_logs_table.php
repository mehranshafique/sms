<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Who did it?
            $table->unsignedBigInteger('institution_id')->nullable(); // Where did it happen?
            $table->string('action'); // e.g., "Login", "Create Student", "Update Settings"
            $table->string('module'); // e.g., "Auth", "Academics", "Finance"
            $table->text('description')->nullable(); // Detailed info (e.g., "Deleted Student John Doe")
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable(); // Browser/Device info
            $table->json('old_values')->nullable(); // For updates (Snapshot before)
            $table->json('new_values')->nullable(); // For updates (Snapshot after)
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};