<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_levels', function (Blueprint $table) {
            // 1. Drop the foreign key that might be relying on the index (safeguard)
            // Note: Laravel usually names FKs as table_column_foreign
            $table->dropForeign(['institution_id']);

            // 2. Drop the old strict unique constraint
            $table->dropUnique('grade_levels_institution_id_name_unique');

            // 3. Re-add the foreign key
            $table->foreign('institution_id')
                  ->references('id')
                  ->on('institutions')
                  ->cascadeOnDelete();

            // 4. Add the new flexible constraint (name + institution + cycle)
            $table->unique(['institution_id', 'name', 'education_cycle'], 'grade_levels_inst_name_cycle_unique');
        });
    }

    public function down(): void
    {
        Schema::table('grade_levels', function (Blueprint $table) {
            // 1. Drop the new flexible constraint
            $table->dropUnique('grade_levels_inst_name_cycle_unique');

            // 2. Drop FK to allow index manipulation safely
            $table->dropForeign(['institution_id']);

            // 3. Add back the old strict constraint
            $table->unique(['institution_id', 'name'], 'grade_levels_institution_id_name_unique');

            // 4. Restore FK
            $table->foreign('institution_id')
                  ->references('id')
                  ->on('institutions')
                  ->cascadeOnDelete();
        });
    }
};