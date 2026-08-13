<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_meetings', function (Blueprint $table) {
            $table->string('scope', 20)->default('individual')->after('institution_id');
            $table->foreignId('class_section_id')->nullable()->after('student_id')
                ->constrained('class_sections')->nullOnDelete();
            $table->uuid('batch_id')->nullable()->after('class_section_id');
            $table->index(['institution_id', 'scope']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('parent_meetings', function (Blueprint $table) {
            $table->dropIndex(['institution_id', 'scope']);
            $table->dropIndex(['batch_id']);
            $table->dropConstrainedForeignId('class_section_id');
            $table->dropColumn(['scope', 'batch_id']);
        });
    }
};
