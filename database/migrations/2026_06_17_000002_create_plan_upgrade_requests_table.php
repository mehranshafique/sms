<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_upgrade_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->foreignId('requested_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending'); // pending | contacted | approved | rejected
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_upgrade_requests');
    }
};
