<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('chatbot_keywords', 'portal_role') && !Schema::hasColumn('chatbot_keywords', 'menu_profile')) {
            DB::statement("ALTER TABLE `chatbot_keywords` CHANGE `portal_role` `menu_profile` VARCHAR(32) NOT NULL DEFAULT 'student'");
        } elseif (!Schema::hasColumn('chatbot_keywords', 'menu_profile')) {
            Schema::table('chatbot_keywords', function (Blueprint $table) {
                $table->string('menu_profile', 32)->default('student')->after('language');
            });
        }

        if (Schema::hasColumn('chat_sessions', 'portal_role') && !Schema::hasColumn('chat_sessions', 'menu_profile')) {
            DB::statement('ALTER TABLE `chat_sessions` CHANGE `portal_role` `menu_profile` VARCHAR(32) NULL');
        } elseif (!Schema::hasColumn('chat_sessions', 'menu_profile')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->string('menu_profile', 32)->nullable()->after('status');
            });
        }

        Schema::table('chat_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_sessions', 'participant_type')) {
                $table->string('participant_type', 32)->nullable()->after('menu_profile');
            }
            if (!Schema::hasColumn('chat_sessions', 'chatbot_keyword_id')) {
                $table->foreignId('chatbot_keyword_id')->nullable()->after('institution_id')
                    ->constrained('chatbot_keywords')->nullOnDelete();
            }
        });

        if (!Schema::hasTable('chatbot_keyword_allowed_roles')) {
            Schema::create('chatbot_keyword_allowed_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatbot_keyword_id')->constrained('chatbot_keywords')->cascadeOnDelete();
                $table->unsignedBigInteger('role_id');
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->unique(['chatbot_keyword_id', 'role_id']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_keyword_allowed_roles');

        Schema::table('chat_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('chat_sessions', 'chatbot_keyword_id')) {
                $table->dropForeign(['chatbot_keyword_id']);
                $table->dropColumn('chatbot_keyword_id');
            }
            if (Schema::hasColumn('chat_sessions', 'participant_type')) {
                $table->dropColumn('participant_type');
            }
        });

        if (Schema::hasColumn('chat_sessions', 'menu_profile') && !Schema::hasColumn('chat_sessions', 'portal_role')) {
            DB::statement('ALTER TABLE `chat_sessions` CHANGE `menu_profile` `portal_role` VARCHAR(32) NULL');
        }

        if (Schema::hasColumn('chatbot_keywords', 'menu_profile') && !Schema::hasColumn('chatbot_keywords', 'portal_role')) {
            DB::statement("ALTER TABLE `chatbot_keywords` CHANGE `menu_profile` `portal_role` VARCHAR(32) NOT NULL DEFAULT 'student'");
        }
    }
};
