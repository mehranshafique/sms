<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('institutions')) {
            return;
        }

        DB::statement("ALTER TABLE institutions MODIFY COLUMN type ENUM('primary', 'secondary', 'university', 'vocational', 'mixed') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE institutions MODIFY COLUMN type ENUM('primary', 'secondary', 'university', 'vocational') NOT NULL");
    }
};
