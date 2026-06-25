<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('reference_no', 32)->unique();
            $table->enum('incident_type', [
                'late_arrival',
                'unexcused_absence',
                'warning',
                'suspension',
                'parent_meeting',
                'parent_summoned',
                'other',
            ]);
            $table->enum('severity', ['minor', 'moderate', 'major'])->default('minor');
            $table->enum('status', ['active', 'resolved', 'cancelled'])->default('active');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('action_taken')->nullable();
            $table->date('incident_date');
            $table->boolean('notify_parents')->default(true);
            $table->timestamp('parents_notified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'student_id', 'incident_date'], 'disc_inst_student_date_idx');
            $table->index(['institution_id', 'status'], 'disc_inst_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_records');
    }
};
