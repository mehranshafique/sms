<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            if (!Schema::hasColumn('institutions', 'head_person_name')) {
                $table->string('head_person_name', 150)->nullable()->after('email');
            }
            if (!Schema::hasColumn('institutions', 'head_person_phone')) {
                $table->string('head_person_phone', 30)->nullable()->after('head_person_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            if (Schema::hasColumn('institutions', 'head_person_phone')) {
                $table->dropColumn('head_person_phone');
            }
            if (Schema::hasColumn('institutions', 'head_person_name')) {
                $table->dropColumn('head_person_name');
            }
        });
    }
};
