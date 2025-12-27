<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Countries Table
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('sortname', 3); // ISO code (e.g., US, CD)
                $table->string('name', 150);
                $table->integer('phonecode');
                $table->timestamps();
            });
        }

        // 2. States Table
        if (!Schema::hasTable('states')) {
            Schema::create('states', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 3. Cities Table
        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 4. Communes (Linked to City)
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communes');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};