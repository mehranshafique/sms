<?php

namespace App\Services\Voting;

use App\Models\AcademicSession;
use App\Models\Election;
use App\Models\ElectoralCycle;
use App\Models\StudentEnrollment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ElectoralCycleService
{
    public function create(array $data, int $institutionId): ElectoralCycle
    {
        $cycle = ElectoralCycle::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $data['academic_session_id'] ?? AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->value('id'),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        AuditLogger::log('Create', 'Voting', 'Electoral cycle created: '.$cycle->title);

        return $cycle;
    }

    public function createElection(ElectoralCycle $cycle, array $data): Election
    {
        $election = Election::create([
            'institution_id' => $cycle->institution_id,
            'electoral_cycle_id' => $cycle->id,
            'academic_session_id' => $data['academic_session_id'] ?? $cycle->academic_session_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => Election::STATUS_DRAFT,
            'eligibility_type' => $data['eligibility_type'] ?? Election::ELIGIBILITY_ALL,
            'eligibility_ids' => $data['eligibility_ids'] ?? [],
            'max_choices' => $data['max_choices'] ?? 1,
            'results_visible_to_voters' => (bool) ($data['results_visible_to_voters'] ?? false),
        ]);

        AuditLogger::log('Create', 'Voting', 'Election created under cycle #'.$cycle->id.': '.$election->title);

        return $election;
    }
}
