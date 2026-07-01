<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['budget_id', 'user_id']);
        });

        // Backfill from responsible_user_id
        $budgets = DB::table('budgets')->whereNotNull('responsible_user_id')->get(['id', 'responsible_user_id']);
        foreach ($budgets as $budget) {
            DB::table('budget_notification_recipients')->insertOrIgnore([
                'budget_id' => $budget->id,
                'user_id' => $budget->responsible_user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_notification_recipients');
    }
};
