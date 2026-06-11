<?php

namespace App\Http\Controllers;

use App\Models\StateExam;
use App\Models\StateExamCandidate;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Services\GradeMentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StateExamController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('state_exam.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        $exams = StateExam::with(['academicSession', 'candidates'])
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->latest()
            ->paginate(15);

        $sessions = AcademicSession::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->orderByDesc('is_current')
            ->get();

        return view('state_exams.index', compact('exams', 'sessions'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'level' => 'required|in:primary_6,secondary_8',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'exam_date' => 'nullable|date',
            'centre' => 'nullable|string|max:150',
        ]);

        StateExam::create($data + [
            'institution_id' => $institutionId,
            'status' => 'open',
        ]);

        return redirect()->route('state-exams.index')->with('success', __('state_exam.created'));
    }

    public function show(StateExam $stateExam)
    {
        $this->authorizeInstitution($stateExam->institution_id);
        $stateExam->load(['candidates.student', 'academicSession']);

        $students = Student::where('institution_id', $stateExam->institution_id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('state_exams.show', compact('stateExam', 'students'));
    }

    public function registerCandidate(Request $request, StateExam $stateExam)
    {
        $this->authorizeInstitution($stateExam->institution_id);
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'candidate_number' => 'nullable|string|max:50',
        ]);

        StateExamCandidate::updateOrCreate(
            ['state_exam_id' => $stateExam->id, 'student_id' => $data['student_id']],
            ['candidate_number' => $data['candidate_number'], 'status' => 'registered']
        );

        return back()->with('success', __('state_exam.candidate_registered'));
    }

    public function updateCandidate(Request $request, StateExam $stateExam, StateExamCandidate $candidate)
    {
        $this->authorizeInstitution($stateExam->institution_id);
        abort_unless($candidate->state_exam_id === $stateExam->id, 404);

        $data = $request->validate([
            'score' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:registered,passed,failed,absent',
        ]);

        $mention = null;
        if (isset($data['score'])) {
            $mention = app(GradeMentionService::class)->fromPercentage((float) $data['score']);
        }

        $candidate->update($data + ['mention' => $mention]);

        return back()->with('success', __('state_exam.candidate_updated'));
    }

    private function authorizeInstitution(?int $institutionId): void
    {
        $active = $this->getInstitutionId();
        if ($active && $institutionId && (int) $active !== (int) $institutionId) {
            abort(403);
        }
    }
}
