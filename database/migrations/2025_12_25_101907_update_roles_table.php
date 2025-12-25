<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'institution_id')) {
                $table->unsignedBigInteger('institution_id')->nullable()->after('id');
                $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
                
                // Drop standard unique index and add scoped unique index
                // Note: Spatie sets a unique index on (name, guard_name). 
                // We drop it to allow duplicate names across different institutions.
                $table->dropUnique(['name', 'guard_name']);
                $table->unique(['name', 'guard_name', 'institution_id']);
            }
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
            // Re-adding the original unique constraint might fail if duplicates exist now, 
            // so use caution or manually handle data cleanup if rolling back.
        });
    }
};