<?php

namespace App\Http\Controllers;

use App\Models\LmdDeliberation;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Services\LmdCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LmdDeliberationController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('lmd_deliberation.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $semester = (int) $request->input('semester', 1);

        $session = AcademicSession::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->where('is_current', true)
            ->first();

        $deliberations = LmdDeliberation::with('student')
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->when($session, fn ($q) => $q->where('academic_session_id', $session->id))
            ->where('semester', $semester)
            ->latest()
            ->paginate(20);

        return view('lmd.deliberations.index', compact('deliberations', 'session', 'semester'));
    }

    public function generate(Request $request, LmdCalculationService $lmdService)
    {
        $institutionId = $this->getInstitutionId();
        $data = $request->validate([
            'semester' => 'required|integer|in:1,2',
            'academic_session_id' => 'required|exists:academic_sessions,id',
        ]);

        $students = Student::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->get();

        $count = 0;
        foreach ($students as $student) {
            $result = $lmdService->calculateSemesterResults($student, $data['academic_session_id'], $data['semester']);
            if (!$result) {
                continue;
            }

            $decision = ($result['decision'] ?? '') === __('lmd.admitted') ? 'admitted' : 'adjourned';
            if ($decision === 'adjourned' && (float) ($result['average'] ?? 0) >= 8) {
                $decision = 'rattrapage';
            }

            LmdDeliberation::updateOrCreate(
                [
                    'academic_session_id' => $data['academic_session_id'],
                    'student_id' => $student->id,
                    'semester' => $data['semester'],
                ],
                [
                    'institution_id' => $institutionId,
                    'average' => $result['average'],
                    'mention' => $result['mention'] ?? null,
                    'decision' => $decision,
                    'status' => 'draft',
                ]
            );
            $count++;
        }

        return back()->with('success', __('lmd_deliberation.generated', ['count' => $count]));
    }

    public function validateDeliberation(LmdDeliberation $deliberation)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && (int) $deliberation->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $deliberation->update([
            'status' => 'validated',
            'validated_by' => Auth::id(),
            'validated_at' => now(),
        ]);

        return back()->with('success', __('lmd_deliberation.validated'));
    }
}
