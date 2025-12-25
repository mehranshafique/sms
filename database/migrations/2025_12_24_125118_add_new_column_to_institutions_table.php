<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('institutions', function (Blueprint $table) {
            if (!Schema::hasColumn('institutions', 'sms_credits')) {
                $table->integer('sms_credits')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('institutions', 'whatsapp_credits')) {
                $table->integer('whatsapp_credits')->default(0)->after('sms_credits');
            }
        });
    }

    public function down()
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['sms_credits', 'whatsapp_credits']);
        });
    }
};