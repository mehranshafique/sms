<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\ClassSection;
use App\Models\Department;
use App\Models\Election;
use App\Models\ElectionVoter;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Services\Voting\ElectionEligibilityService;
use App\Services\Voting\ElectionResultsService;
use App\Services\Voting\ElectionStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ElectionController extends BaseController
{
    public function __construct(
        protected ElectionEligibilityService $eligibility,
        protected ElectionStatusService $statusService,
        protected ElectionResultsService $resultsService
    ) {
        $this->authorizeResource(Election::class, 'election');
        $this->setPageTitle(__('voting.election_management'));
    }

    public function index(Request $request)
    {
        // Prefer cycles as the entry point
        return redirect()->route('electoral-cycles.index');
    }

    public function create()
    {
        return redirect()->route('electoral-cycles.index');
    }

    public function store(Request $request)
    {
        return response()->json(['message' => __('voting.use_cycle_to_create')], 422);
    }

    public function show(Election $election)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && (int) $election->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $election->load([
            'cycle',
            'positions.candidates.student.classSection',
            'voters' => fn ($q) => $q->latest('id')->limit(200),
        ]);

        $turnout = $this->resultsService->turnout($election);
        $lists = $this->resultsService->participationLists($election);
        $results = $this->resultsService->canViewResults($election, true)
            ? $this->resultsService->resultsByPosition($election)
            : [];

        $grades = GradeLevel::where('institution_id', $election->institution_id)->orderBy('name')->get();
        $sections = ClassSection::with('gradeLevel')
            ->where('institution_id', $election->institution_id)
            ->orderBy('name')
            ->get();
        $departments = Department::where('institution_id', $election->institution_id)->orderBy('name')->get();

        $tab = request('tab', 'ballot');

        return view('elections.show', compact(
            'election', 'turnout', 'lists', 'results', 'grades', 'sections', 'departments', 'tab'
        ));
    }

    public function updateSettings(Request $request, Election $election)
    {
        $this->authorize('update', $election);

        if (! in_array($election->status, [Election::STATUS_DRAFT, Election::STATUS_SCHEDULED], true)) {
            return back()->with('error', __('voting.settings_locked'));
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'eligibility_type' => 'required|in:all_students,grade_levels,class_sections,departments,manual_list',
            'eligibility_ids' => 'nullable|array',
            'eligibility_ids.*' => 'integer',
            'results_visible_to_voters' => 'nullable|boolean',
        ]);

        $election->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'eligibility_type' => $data['eligibility_type'],
            'eligibility_ids' => $data['eligibility_ids'] ?? [],
            'results_visible_to_voters' => (bool) ($data['results_visible_to_voters'] ?? false),
        ]);

        if ($election->eligibility_type !== Election::ELIGIBILITY_MANUAL) {
            $this->eligibility->syncFromRules($election);
        }

        return back()->with('success', __('voting.settings_saved'));
    }

    public function addPosition(Request $request, Election $election)
    {
        $this->authorize('update', $election);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'integer|min:0',
        ]);

        $election->positions()->create($validated);

        return response()->json(['message' => __('voting.position_added'), 'redirect' => route('elections.show', ['election' => $election, 'tab' => 'ballot'])]);
    }

    public function addCandidate(Request $request, Election $election)
    {
        $this->authorize('update', $election);

        $validated = $request->validate([
            'election_position_id' => 'required|exists:election_positions,id',
            'mode' => 'required|in:digitex,external',
            'admission_number' => 'nullable|string',
            'external_name' => 'nullable|string|max:255',
            'external_class_label' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:100',
            'external_photo' => 'nullable|image|max:2048',
            'manifesto' => 'nullable|string|max:2000',
        ]);

        $positionOk = $election->positions()->where('id', $validated['election_position_id'])->exists();
        if (! $positionOk) {
            return response()->json(['message' => __('voting.invalid_position')], 422);
        }

        if ($validated['mode'] === 'digitex') {
            $student = Student::where('admission_number', $validated['admission_number'] ?? '')
                ->where('institution_id', $election->institution_id)
                ->first();

            if (! $student) {
                return response()->json(['message' => __('voting.student_not_found')], 422);
            }

            $exists = Candidate::where('election_position_id', $validated['election_position_id'])
                ->where('student_id', $student->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => __('voting.candidate_exists')], 422);
            }

            Candidate::create([
                'election_id' => $election->id,
                'election_position_id' => $validated['election_position_id'],
                'student_id' => $student->id,
                'manifesto' => $validated['manifesto'] ?? null,
                'status' => 'approved',
            ]);
        } else {
            if (empty($validated['external_name'])) {
                return response()->json(['message' => __('voting.external_name_required')], 422);
            }

            $photoPath = null;
            if ($request->hasFile('external_photo')) {
                $photoPath = $request->file('external_photo')->store('election-candidates', 'public');
            }

            Candidate::create([
                'election_id' => $election->id,
                'election_position_id' => $validated['election_position_id'],
                'student_id' => null,
                'external_name' => $validated['external_name'],
                'external_class_label' => $validated['external_class_label'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'external_photo' => $photoPath,
                'manifesto' => $validated['manifesto'] ?? null,
                'status' => 'approved',
            ]);
        }

        return response()->json(['message' => __('voting.candidate_added'), 'redirect' => route('elections.show', ['election' => $election, 'tab' => 'ballot'])]);
    }

    public function destroyCandidate(Candidate $candidate)
    {
        $this->authorize('update', $candidate->election);
        if ($candidate->external_photo) {
            Storage::disk('public')->delete($candidate->external_photo);
        }
        $candidate->delete();

        return response()->json(['message' => __('voting.success_delete')]);
    }

    public function syncVoters(Election $election)
    {
        $this->authorize('update', $election);
        $created = $this->eligibility->syncFromRules($election);

        return back()->with('success', __('voting.voters_synced', ['count' => $created]));
    }

    public function addVoter(Request $request, Election $election)
    {
        $this->authorize('update', $election);

        $data = $request->validate([
            'mode' => 'required|in:digitex,external',
            'admission_number' => 'nullable|string',
            'display_name' => 'nullable|string|max:255',
            'voter_code' => 'nullable|string|max:64',
            'external_class_label' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:50',
        ]);

        if ($data['mode'] === 'digitex') {
            $student = Student::where('admission_number', $data['admission_number'] ?? '')
                ->where('institution_id', $election->institution_id)
                ->first();
            if (! $student) {
                return back()->with('error', __('voting.student_not_found'));
            }
            $this->eligibility->addManualVoter($election, ['student_id' => $student->id]);
        } else {
            if (empty($data['display_name'])) {
                return back()->with('error', __('voting.external_name_required'));
            }
            $this->eligibility->addManualVoter($election, $data);
        }

        return back()->with('success', __('voting.voter_added'));
    }

    public function importVoters(Request $request, Election $election)
    {
        $this->authorize('update', $election);

        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:2048']);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $rows = collect();
        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($header ?: [], $data);
            if (! is_array($row)) {
                continue;
            }
            $rows->push([
                'voter_code' => $row['voter_code'] ?? $row['code'] ?? null,
                'display_name' => $row['display_name'] ?? $row['name'] ?? null,
                'external_class_label' => $row['class'] ?? $row['external_class_label'] ?? null,
                'contact_phone' => $row['phone'] ?? $row['contact_phone'] ?? null,
            ]);
        }
        fclose($handle);

        $count = $this->eligibility->importRows($election, $rows);

        return back()->with('success', __('voting.voters_imported', ['count' => $count]));
    }

    public function destroyVoter(ElectionVoter $electionVoter)
    {
        $this->authorize('update', $electionVoter->election);
        if ($electionVoter->election->participations()->where('election_voter_id', $electionVoter->id)->exists()) {
            return back()->with('error', __('voting.cannot_delete_voted'));
        }
        $electionVoter->delete();

        return back()->with('success', __('voting.voter_deleted'));
    }

    public function open(Election $election)
    {
        $this->authorize('update', $election);
        try {
            $this->statusService->open($election);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('voting.election_opened')]);
    }

    public function close(Election $election)
    {
        $this->authorize('update', $election);
        try {
            $this->statusService->close($election);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('voting.election_closed')]);
    }

    /** @deprecated use open() */
    public function publish(Election $election)
    {
        return $this->open($election);
    }

    public function publishResults(Election $election)
    {
        $this->authorize('update', $election);
        try {
            $this->statusService->publishResults($election);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('voting.results_published')]);
    }

    public function exportTurnout(Election $election): StreamedResponse
    {
        $this->authorize('view', $election);
        $lists = $this->resultsService->participationLists($election);

        return response()->streamDownload(function () use ($lists) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['status', 'voter_code', 'display_name']);
            foreach ($lists['voted'] as $v) {
                fputcsv($out, ['voted', $v->voter_code, $v->displayName()]);
            }
            foreach ($lists['notVoted'] as $v) {
                fputcsv($out, ['not_voted', $v->voter_code, $v->displayName()]);
            }
            fclose($out);
        }, 'election-'.$election->id.'-turnout.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportResults(Election $election): StreamedResponse
    {
        $this->authorize('view', $election);
        if (! $this->resultsService->canViewResults($election, true)) {
            abort(403, __('voting.results_not_available'));
        }
        $results = $this->resultsService->resultsByPosition($election);

        return response()->streamDownload(function () use ($results) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['position', 'candidate', 'votes', 'percent']);
            foreach ($results as $block) {
                foreach ($block['candidates'] as $c) {
                    fputcsv($out, [$block['position_name'], $c['name'], $c['votes'], $c['percent']]);
                }
            }
            fclose($out);
        }, 'election-'.$election->id.'-results.csv', ['Content-Type' => 'text/csv']);
    }

    public function destroy(Election $election)
    {
        $this->authorize('delete', $election);
        $election->delete();

        return response()->json(['message' => __('voting.success_delete')]);
    }
}
