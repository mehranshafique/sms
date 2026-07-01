<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_event_invitations', function (Blueprint $table) {
            $table->string('recipient_telegram_chat_id')->nullable()->after('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::table('school_event_invitations', function (Blueprint $table) {
            $table->dropColumn('recipient_telegram_chat_id');
        });
    }
};
