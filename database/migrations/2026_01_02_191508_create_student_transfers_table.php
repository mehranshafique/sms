<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->date('transfer_date');
            $table->string('reason');
            $table->string('conduct')->nullable(); // Good, Excellent, etc.
            $table->string('leaving_class')->nullable(); // Class they left from
            $table->enum('status', ['transferred', 'withdrawn', 'expelled'])->default('transferred');
            $table->text('remarks')->nullable();
            
            // FIX: Added ->nullable() here so 'nullOnDelete' can actually work
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_transfers');
    }
};