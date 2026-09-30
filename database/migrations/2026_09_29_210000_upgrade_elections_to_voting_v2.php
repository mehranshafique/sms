<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electoral_cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('academic_session_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('status', 32)->default('draft'); // draft|active|closed
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->nullOnDelete();
        });

        Schema::table('elections', function (Blueprint $table) {
            $table->unsignedBigInteger('electoral_cycle_id')->nullable()->after('institution_id');
            $table->string('eligibility_type', 32)->default('all_students')->after('status');
            $table->json('eligibility_ids')->nullable()->after('eligibility_type');
            $table->unsignedTinyInteger('max_choices')->default(1)->after('eligibility_ids');
            $table->boolean('results_visible_to_voters')->default(false)->after('max_choices');
        });

        // Map legacy statuses → V2
        DB::table('elections')->where('status', 'published')->update(['status' => 'open']);
        DB::table('elections')->where('status', 'ongoing')->update(['status' => 'open']);
        DB::table('elections')->where('status', 'completed')->update(['status' => 'closed']);

        // Wrap existing elections in a migrated cycle
        $elections = DB::table('elections')->whereNull('electoral_cycle_id')->get();
        foreach ($elections as $election) {
            $cycleId = DB::table('electoral_cycles')->insertGetId([
                'institution_id' => $election->institution_id,
                'academic_session_id' => $election->academic_session_id,
                'title' => 'Migrated — '.$election->title,
                'description' => 'Auto-created during Voting V2 upgrade',
                'start_date' => $election->start_date,
                'end_date' => $election->end_date,
                'status' => in_array($election->status, ['closed', 'results_published'], true) ? 'closed' : 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('elections')->where('id', $election->id)->update(['electoral_cycle_id' => $cycleId]);
        }

        Schema::table('elections', function (Blueprint $table) {
            $table->foreign('electoral_cycle_id')->references('id')->on('electoral_cycles')->nullOnDelete();
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->string('external_name')->nullable()->after('student_id');
            $table->string('external_photo')->nullable()->after('external_name');
            $table->string('external_class_label')->nullable()->after('external_photo');
            $table->string('contact_phone')->nullable()->after('external_class_label');
            $table->string('contact_email')->nullable()->after('contact_phone');
        });

        // Allow independent (non-DigiteX) candidates
        try {
            DB::statement('ALTER TABLE candidates MODIFY student_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // SQLite / already-nullable environments
        }

        Schema::create('election_voters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('voter_code', 64);
            $table->string('display_name')->nullable();
            $table->string('external_class_label')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('source', 32)->default('digitex'); // digitex|manual|import
            $table->timestamps();

            $table->unique(['election_id', 'voter_code']);
            $table->unique(['election_id', 'student_id']);
            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
        });

        Schema::create('election_participations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('election_voter_id');
            $table->timestamp('participated_at');
            $table->string('device_id')->nullable();
            $table->timestamps();

            $table->unique(['election_id', 'election_voter_id']);
            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('election_voter_id')->references('id')->on('election_voters')->cascadeOnDelete();
        });

        Schema::create('ballots', function (Blueprint $table) {
            $table->id();
            $table->uuid('ballot_uuid')->unique();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('election_position_id');
            $table->unsignedBigInteger('candidate_id');
            $table->timestamp('cast_at');
            $table->timestamps();

            $table->index(['election_id', 'election_position_id']);
            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('election_position_id')->references('id')->on('election_positions')->cascadeOnDelete();
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
        });

        // Migrate legacy linked votes → participation + anonymous ballot
        if (Schema::hasTable('votes')) {
            $legacyVotes = DB::table('votes')->orderBy('id')->get();
            $voterCache = [];

            foreach ($legacyVotes->groupBy(fn ($v) => $v->election_id.'-'.$v->voter_id) as $group) {
                $first = $group->first();
                $cacheKey = $first->election_id.':'.$first->voter_id;

                if (! isset($voterCache[$cacheKey])) {
                    $student = DB::table('students')->where('id', $first->voter_id)->first();
                    $voterId = DB::table('election_voters')->insertGetId([
                        'election_id' => $first->election_id,
                        'student_id' => $first->voter_id,
                        'voter_code' => 'STU-'.$first->voter_id,
                        'display_name' => $student
                            ? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
                            : 'Student #'.$first->voter_id,
                        'source' => 'digitex',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $voterCache[$cacheKey] = $voterId;

                    DB::table('election_participations')->insert([
                        'election_id' => $first->election_id,
                        'election_voter_id' => $voterId,
                        'participated_at' => $first->voted_at ?? now(),
                        'device_id' => $first->device_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($group as $vote) {
                    DB::table('ballots')->insert([
                        'ballot_uuid' => (string) Str::uuid(),
                        'election_id' => $vote->election_id,
                        'election_position_id' => $vote->election_position_id,
                        'candidate_id' => $vote->candidate_id,
                        'cast_at' => $vote->voted_at ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::rename('votes', 'votes_legacy_v1');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('votes_legacy_v1') && ! Schema::hasTable('votes')) {
            Schema::rename('votes_legacy_v1', 'votes');
        }

        Schema::dropIfExists('ballots');
        Schema::dropIfExists('election_participations');
        Schema::dropIfExists('election_voters');

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'external_name',
                'external_photo',
                'external_class_label',
                'contact_phone',
                'contact_email',
            ]);
        });

        Schema::table('elections', function (Blueprint $table) {
            $table->dropForeign(['electoral_cycle_id']);
            $table->dropColumn([
                'electoral_cycle_id',
                'eligibility_type',
                'eligibility_ids',
                'max_choices',
                'results_visible_to_voters',
            ]);
        });

        Schema::dropIfExists('electoral_cycles');
    }
};
