<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Elections (The Event)
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('academic_session_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('status')->default('draft'); // draft, scheduled, ongoing, completed, published
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions');
        });

        // 2. Positions (e.g., Class Prefect, Head Boy)
        Schema::create('election_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id');
            $table->string('name'); // e.g., "Class President"
            $table->integer('sequence')->default(0); // For ordering pages in the voting app
            $table->timestamps();

            $table->foreign('election_id')->references('id')->on('elections')->onDelete('cascade');
        });

        // 3. Candidates
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('election_position_id');
            $table->unsignedBigInteger('student_id'); // Link to Student Profile
            $table->text('manifesto')->nullable();
            $table->string('status')->default('approved'); // approved, disqualified
            $table->timestamps();

            $table->foreign('election_id')->references('id')->on('elections')->onDelete('cascade');
            $table->foreign('election_position_id')->references('id')->on('election_positions')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });

        // 4. Votes (Ledger)
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('election_position_id');
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('voter_id'); // Student ID who voted
            $table->string('device_id')->nullable(); // For audit (NFC Reader ID)
            $table->timestamp('voted_at');

            // Prevent duplicate voting for the same position in the same election
            $table->unique(['voter_id', 'election_position_id'], 'unique_vote_per_position');

            $table->foreign('election_id')->references('id')->on('elections')->onDelete('cascade');
            $table->foreign('election_position_id')->references('id')->on('election_positions')->onDelete('cascade');
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->foreign('voter_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('election_positions');
        Schema::dropIfExists('elections');
    }
};