<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reenrollment_campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('reenrollment_campaigns', 'invitations_sent_at')) {
                $table->timestamp('invitations_sent_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('reenrollment_campaigns', 'invitations_sent_count')) {
                $table->unsignedInteger('invitations_sent_count')->default(0)->after('invitations_sent_at');
            }
            if (! Schema::hasColumn('reenrollment_campaigns', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('invitations_sent_count');
            }
        });

        Schema::table('reenrollment_confirmations', function (Blueprint $table) {
            if (! Schema::hasColumn('reenrollment_confirmations', 'invitation_sent_at')) {
                $table->timestamp('invitation_sent_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('reenrollment_confirmations', 'last_reminder_at')) {
                $table->timestamp('last_reminder_at')->nullable()->after('invitation_sent_at');
            }
            if (! Schema::hasColumn('reenrollment_confirmations', 'reminder_count')) {
                $table->unsignedSmallInteger('reminder_count')->default(0)->after('last_reminder_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reenrollment_campaigns', function (Blueprint $table) {
            $table->dropColumn(['invitations_sent_at', 'invitations_sent_count', 'closed_at']);
        });

        Schema::table('reenrollment_confirmations', function (Blueprint $table) {
            $table->dropColumn(['invitation_sent_at', 'last_reminder_at', 'reminder_count']);
        });
    }
};
