<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('packages', 'ai_enabled')) {
            return;
        }

        $proKeywords = ['premium', 'enterprise', 'platinum', 'gold', 'pro', 'ultimate'];
        $unlimitedKeywords = ['enterprise', 'premium', 'ultimate'];

        foreach ($proKeywords as $keyword) {
            DB::table('packages')
                ->whereRaw('LOWER(name) LIKE ?', ['%' . $keyword . '%'])
                ->update(['ai_enabled' => true]);
        }

        foreach ($unlimitedKeywords as $keyword) {
            DB::table('packages')
                ->whereRaw('LOWER(name) LIKE ?', ['%' . $keyword . '%'])
                ->update(['ai_unlimited' => true]);
        }

        DB::table('packages')
            ->where('ai_enabled', false)
            ->whereNotNull('modules')
            ->get()
            ->each(function ($pkg) {
                $modules = json_decode($pkg->modules, true);
                if (is_array($modules) && count($modules) >= (int) config('plan.ai_module_threshold', 40)) {
                    DB::table('packages')->where('id', $pkg->id)->update(['ai_enabled' => true]);
                }
            });
    }

    public function down(): void
    {
        // Non-destructive rollback
    }
};
