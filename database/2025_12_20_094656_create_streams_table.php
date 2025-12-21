<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('streams')) {
            Schema::create('streams', function (Blueprint $table) {
                $table->id();
                
                // Tenant Isolation
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                
                $table->string('name', 100); // e.g., "Science", "Commercial"
                $table->string('code', 30)->nullable();
                
                // Optional: Link to specific grade levels if streams are grade-specific
                // But usually, a Stream like "Science" is a global entity in the school used across grades 10-12.
                // We'll keep it flexible.
                
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Unique Name per Institution
                $table->unique(['institution_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};