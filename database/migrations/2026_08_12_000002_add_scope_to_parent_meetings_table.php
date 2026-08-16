<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parent_meetings')) {
            return;
        }

        // These columns were later folded into create_parent_meetings_table.
        if (Schema::hasColumn('parent_meetings', 'scope')
            && Schema::hasColumn('parent_meetings', 'class_section_id')
            && Schema::hasColumn('parent_meetings', 'batch_id')) {
            return;
        }

        Schema::table('parent_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_meetings', 'scope')) {
                $table->string('scope', 20)->default('individual')->after('institution_id');
            }
            if (!Schema::hasColumn('parent_meetings', 'class_section_id')) {
                $table->foreignId('class_section_id')->nullable()->after('student_id')
                    ->constrained('class_sections')->nullOnDelete();
            }
            if (!Schema::hasColumn('parent_meetings', 'batch_id')) {
                $table->uuid('batch_id')->nullable()->after('class_section_id');
            }
        });

        if (!Schema::hasIndex('parent_meetings', 'parent_meetings_institution_id_scope_index')) {
            Schema::table('parent_meetings', function (Blueprint $table) {
                $table->index(['institution_id', 'scope']);
            });
        }
        if (!Schema::hasIndex('parent_meetings', 'parent_meetings_batch_id_index')) {
            Schema::table('parent_meetings', function (Blueprint $table) {
                $table->index('batch_id');
            });
        }
    }

    public function down(): void
    {
        // Columns now live on create_parent_meetings_table; do not drop them here.
    }
};
