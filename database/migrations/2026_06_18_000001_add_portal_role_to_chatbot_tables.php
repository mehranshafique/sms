<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_keywords', function (Blueprint $table) {
            $table->string('menu_profile', 32)->default('student')->after('language');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->string('menu_profile', 32)->nullable()->after('status');
        });

        $roleMap = [
            'digitex' => 'head_officer',
            'headoffice' => 'head_officer',
            'direction' => 'head_officer',
            'admin' => 'school_admin',
            'director' => 'school_admin',
            'directeur' => 'school_admin',
            'agent' => 'teacher',
            'teacher' => 'teacher',
            'enseignant' => 'teacher',
            'prof' => 'teacher',
            'staff' => 'teacher',
            'finance' => 'finance',
            'compta' => 'finance',
            'bonjour' => 'parent',
            'parent' => 'parent',
            'parents' => 'parent',
            'portail' => 'student',
            'student' => 'student',
            'eleve' => 'student',
            'etudiant' => 'student',
            'hello' => 'student',
            'hi' => 'student',
            'start' => 'student',
            'salut' => 'student',
            'menu' => 'student',
        ];

        foreach (DB::table('chatbot_keywords')->get() as $keyword) {
            $inferred = $roleMap[strtolower($keyword->keyword)] ?? 'student';
            DB::table('chatbot_keywords')->where('id', $keyword->id)->update(['menu_profile' => $inferred]);
        }
    }

    public function down(): void
    {
        Schema::table('chatbot_keywords', function (Blueprint $table) {
            $table->dropColumn('menu_profile');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn('menu_profile');
        });
    }
};
