<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_event_invitations', function (Blueprint $table) {
            $table->json('delivery_meta')->nullable()->after('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('school_event_invitations', function (Blueprint $table) {
            $table->dropColumn('delivery_meta');
        });
    }
};
