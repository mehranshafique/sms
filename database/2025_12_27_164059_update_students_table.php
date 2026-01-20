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
        Schema::table('students', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('students', 'country')) {
                $table->string('country')->nullable()->after('mobile_number');
            }
            if (!Schema::hasColumn('students', 'state')) {
                $table->string('state')->nullable()->after('country');
            }
            if (!Schema::hasColumn('students', 'city')) {
                $table->string('city')->nullable()->after('state');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'state',
                'city',
            ]);
        });
    }
};