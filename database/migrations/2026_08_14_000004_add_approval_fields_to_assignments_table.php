<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Default 'approved' so schools that do not require approval — and every
            // homework created before this feature — keep behaving exactly as before.
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('approved')
                ->after('file_path');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('submitted_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->timestamp('published_at')->nullable()->after('rejection_reason');
            $table->timestamp('parents_notified_at')->nullable()->after('published_at');

            $table->index(['class_section_id', 'status']);
        });

        // Existing homework is already visible to parents and students.
        DB::table('assignments')->whereNull('published_at')->update([
            'published_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['class_section_id', 'status']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'status',
                'submitted_at',
                'approved_at',
                'rejection_reason',
                'published_at',
                'parents_notified_at',
            ]);
        });
    }
};
