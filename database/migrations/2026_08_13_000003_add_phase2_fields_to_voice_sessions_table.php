<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_sessions', function (Blueprint $table) {
            $table->boolean('pin_verified')->default(false)->after('menu_profile');
            $table->unsignedTinyInteger('pin_attempts')->default(0)->after('pin_verified');
            $table->unsignedTinyInteger('ai_turns')->default(0)->after('turns');
            $table->timestamp('transferred_at')->nullable()->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('voice_sessions', function (Blueprint $table) {
            $table->dropColumn(['pin_verified', 'pin_attempts', 'ai_turns', 'transferred_at']);
        });
    }
};
