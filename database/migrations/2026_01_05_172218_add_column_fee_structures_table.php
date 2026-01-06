<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Allow fees to be assigned to a specific section (e.g. Class 1-A) overrides Grade Level
            $table->foreignId('class_section_id')->nullable()->after('grade_level_id')->constrained()->onDelete('cascade');
            
            // Ensure these exist if they weren't added by previous migrations
            if (!Schema::hasColumn('fee_structures', 'payment_mode')) {
                $table->enum('payment_mode', ['global', 'installment'])->default('global')->after('amount');
            }
            if (!Schema::hasColumn('fee_structures', 'installment_order')) {
                $table->integer('installment_order')->nullable()->after('payment_mode');
            }
        });
    }

    public function down()
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropForeign(['class_section_id']);
            $table->dropColumn('class_section_id');
        });
    }
};