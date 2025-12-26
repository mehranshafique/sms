<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->nullable()->index(); // Nullable for Global System Alerts
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('info'); // info, warning, urgent
            $table->string('audience')->default('all'); // all, staff, student, parent
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};