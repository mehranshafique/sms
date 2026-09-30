<?php

namespace App\Services\Voting;

use App\Models\Election;
use App\Models\ElectionVoter;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ElectionEligibilityService
{
    /**
     * Rebuild digitex-sourced voters from eligibility rules.
     * Preserves manual/import voters and any voter who already participated.
     */
    public function syncFromRules(Election $election): int
    {
        $studentIds = $this->resolveStudentIds($election);

        $existing = ElectionVoter::where('election_id', $election->id)
            ->where('source', 'digitex')
            ->whereNotNull('student_id')
            ->get()
            ->keyBy('student_id');

        $participatedVoterIds = $election->participations()->pluck('election_voter_id')->all();

        $kept = [];
        $created = 0;

        foreach ($studentIds as $studentId) {
            if ($existing->has($studentId)) {
                $kept[] = $existing[$studentId]->id;
                continue;
            }

            $student = Student::find($studentId);
            if (! $student) {
                continue;
            }

            $voter = ElectionVoter::create([
                'election_id' => $election->id,
                'student_id' => $student->id,
                'voter_code' => 'STU-'.$student->id,
                'display_name' => trim($student->first_name.' '.$student->last_name),
                'source' => 'digitex',
            ]);
            $kept[] = $voter->id;
            $created++;
        }

        // Remove digitex voters no longer eligible, unless they already voted
        ElectionVoter::where('election_id', $election->id)
            ->where('source', 'digitex')
            ->whereNotIn('id', $kept)
            ->whereNotIn('id', $participatedVoterIds)
            ->delete();

        return $created;
    }

    /**
     * @return list<int>
     */
    public function resolveStudentIds(Election $election): array
    {
        $sessionId = $election->academic_session_id;
        $query = StudentEnrollment::query()
            ->where('institution_id', $election->institution_id)
            ->where('status', 'active')
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId));

        $ids = $election->eligibility_ids ?? [];

        switch ($election->eligibility_type) {
            case Election::ELIGIBILITY_GRADES:
                if ($ids === []) {
                    return [];
                }
                $query->whereIn('grade_level_id', $ids);
                break;
            case Election::ELIGIBILITY_SECTIONS:
                if ($ids === []) {
                    return [];
                }
                $query->whereIn('class_section_id', $ids);
                break;
            case Election::ELIGIBILITY_DEPARTMENTS:
                if ($ids === []) {
                    return [];
                }
                $query->whereIn('class_section_id', function ($sub) use ($ids) {
                    $sub->select('class_subjects.class_section_id')
                        ->from('class_subjects')
                        ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
                        ->whereIn('subjects.department_id', $ids);
                });
                break;
            case Election::ELIGIBILITY_MANUAL:
                return ElectionVoter::where('election_id', $election->id)
                    ->whereNotNull('student_id')
                    ->pluck('student_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            case Election::ELIGIBILITY_ALL:
            default:
                break;
        }

        return $query->pluck('student_id')->unique()->map(fn ($id) => (int) $id)->values()->all();
    }

    public function addManualVoter(Election $election, array $data): ElectionVoter
    {
        if (! empty($data['student_id'])) {
            $student = Student::where('institution_id', $election->institution_id)
                ->findOrFail($data['student_id']);

            return ElectionVoter::updateOrCreate(
                ['election_id' => $election->id, 'student_id' => $student->id],
                [
                    'voter_code' => $data['voter_code'] ?? ('STU-'.$student->id),
                    'display_name' => trim($student->first_name.' '.$student->last_name),
                    'source' => 'manual',
                ]
            );
        }

        $code = $data['voter_code'] ?? ('EXT-'.Str::upper(Str::random(8)));

        return ElectionVoter::create([
            'election_id' => $election->id,
            'student_id' => null,
            'voter_code' => $code,
            'display_name' => $data['display_name'],
            'external_class_label' => $data['external_class_label'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'source' => $data['source'] ?? 'manual',
        ]);
    }

    /**
     * @param  Collection<int, array{voter_code?:string,display_name:string,external_class_label?:string,contact_phone?:string}>  $rows
     */
    public function importRows(Election $election, Collection $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row['display_name'])) {
                continue;
            }
            $this->addManualVoter($election, [
                'display_name' => $row['display_name'],
                'voter_code' => $row['voter_code'] ?? null,
                'external_class_label' => $row['external_class_label'] ?? null,
                'contact_phone' => $row['contact_phone'] ?? null,
                'source' => 'import',
            ]);
            $count++;
        }

        return $count;
    }

    public function findVoterForStudent(Election $election, int $studentId): ?ElectionVoter
    {
        return ElectionVoter::where('election_id', $election->id)
            ->where('student_id', $studentId)
            ->first();
    }
}
