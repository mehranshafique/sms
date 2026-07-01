<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_key')->index();
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->text('available_tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institution_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
