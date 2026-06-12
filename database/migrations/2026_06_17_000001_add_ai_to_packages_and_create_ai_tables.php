<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Additive plan flags (existing plans default to NO AI) ---
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'ai_enabled')) {
                $table->boolean('ai_enabled')->default(false)->after('staff_limit');
            }
            if (!Schema::hasColumn('packages', 'ai_unlimited')) {
                $table->boolean('ai_unlimited')->default(false)->after('ai_enabled');
            }
            if (!Schema::hasColumn('packages', 'ai_monthly_limit')) {
                $table->integer('ai_monthly_limit')->nullable()->after('ai_unlimited');
            }
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feature', 60)->default('assistant');
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->string('status', 20)->default('success'); // success | error | blocked
            $table->string('period', 7)->index(); // YYYY-MM
            $table->timestamps();

            $table->index(['institution_id', 'period']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('context', 40)->default('assistant'); // assistant | tutor
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20); // user | assistant | system
            $table->text('content');
            $table->unsignedInteger('tokens')->nullable();
            $table->timestamps();

            $table->index('ai_conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_usage_logs');

        Schema::table('packages', function (Blueprint $table) {
            foreach (['ai_monthly_limit', 'ai_unlimited', 'ai_enabled'] as $col) {
                if (Schema::hasColumn('packages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
