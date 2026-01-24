<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Departments Table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('head_of_department_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Update Subjects Table
        Schema::table('subjects', function (Blueprint $table) {
            // Link to Department (University specific)
            $table->foreignId('department_id')->nullable()->after('grade_level_id')->constrained()->nullOnDelete();
            
            // Prerequisite Logic (Self-referencing)
            $table->foreignId('prerequisite_id')->nullable()->after('type')->constrained('subjects')->nullOnDelete();
            
            // Semester/Term (Simple string/enum for now, or link to a Terms table if complex)
            // '1', '2', 'Fall', 'Spring', etc.
            $table->string('semester')->nullable()->after('code'); 
            
            // Ensure credit_hours is decimal for precision (e.g. 1.5 credits)
            // Note: If credit_hours exists as integer, change it.
            if (Schema::hasColumn('subjects', 'credit_hours')) {
                $table->decimal('credit_hours', 4, 1)->change();
            } else {
                $table->decimal('credit_hours', 4, 1)->nullable()->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            $table->dropForeign(['prerequisite_id']);
            $table->dropColumn('prerequisite_id');
            $table->dropColumn('semester');
        });

        Schema::dropIfExists('departments');
    }
};