<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reenrollment_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('from_academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('to_academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->decimal('min_fee_amount', 12, 2)->default(0);
            $table->date('opens_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->string('status', 32)->default('open'); // draft|open|closed
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['institution_id', 'to_academic_session_id'], 'reenroll_campaign_inst_to_session_uq');
        });

        Schema::create('reenrollment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('reenrollment_campaigns')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_enrollment_id')->nullable()->constrained('student_enrollments')->nullOnDelete();
            $table->foreignId('from_class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->foreignId('proposed_class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->foreignId('approved_class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();

            // pending | partial_confirmation | pending_review | confirmed | rejected | declined | expired
            $table->string('status', 40)->default('pending')->index();

            $table->string('parent_confirmation_channel', 32)->nullable(); // whatsapp|physical|web
            $table->timestamp('parent_confirmed_at')->nullable();
            $table->foreignId('parent_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('parent_note')->nullable();

            $table->decimal('amount_required', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('payment_status', 32)->default('none'); // none|pending|partial|paid

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();

            $table->foreignId('target_enrollment_id')->nullable()->constrained('student_enrollments')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'student_id'], 'reenroll_campaign_student_uq');
            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reenrollment_confirmations');
        Schema::dropIfExists('reenrollment_campaigns');
    }
};
