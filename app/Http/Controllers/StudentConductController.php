<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\StudentConductRecord;
use App\Models\StudentEnrollment;
use App\Services\AcademicCycleService;
use App\Services\StudentConductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentConductController extends BaseController
{
    public function __construct(
        protected AcademicCycleService $cycleService,
        protected StudentConductService $conductService
    ) {
        $this->middleware('auth');
        $this->setPageTitle(__('conduct.page_title'));
    }

    public function index(Request $request)
    {
        $this->authorizeConductAccess();

        $institutionId = $this->getInstitutionId();
        if (!$institutionId) {
            return redirect()->back()->with('error', __('settings.select_institution_first'));
        }

        $sections = $this->allowedSections($institutionId);
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        $classSectionId = $request->integer('class_section_id') ?: null;
        $scopeType = $request->input('scope_type', 'period');
        $scopeKey = $request->input('scope_key', 'p1');

        $students = collect();
        $isPrimary = true;
        $suggestions = [];

        if ($classSectionId && $session) {
            $section = ClassSection::with('gradeLevel')->findOrFail($classSectionId);
            if ((int) $section->institution_id !== (int) $institutionId) {
                abort(403);
            }

            $this->authorizeSectionAccess($section);

            $cycle = $section->gradeLevel?->education_cycle;
            $cycleValue = is_object($cycle) ? $cycle->value : (string) $cycle;
            $isPrimary = ! $this->cycleService->usesSemesterModel($cycleValue);

            if ($isPrimary && $scopeType === 'semester') {
                $scopeType = 'trimester';
            }
            if (! $isPrimary && $scopeType === 'trimester') {
                $scopeType = 'semester';
            }

            $enrollments = StudentEnrollment::with('student')
                ->where('class_section_id', $section->id)
                ->where('academic_session_id', $session->id)
                ->where('status', 'active')
                ->get();

            $existing = StudentConductRecord::query()
                ->where('academic_session_id', $session->id)
                ->where('scope_type', $scopeType)
                ->where('scope_key', (string) $scopeKey)
                ->whereIn('student_id', $enrollments->pluck('student_id'))
                ->get()
                ->keyBy('student_id');

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                if (! $student) {
                    continue;
                }
                $record = $existing->get($student->id);
                $suggestion = $isPrimary
                    ? $this->conductService->suggestForPrimary($student, (int) $session->id)
                    : null;

                $students->push([
                    'student' => $student,
                    'conduct' => $record?->conduct ?? ($suggestion ?? ''),
                    'notes' => $record?->notes ?? '',
                    'suggested' => $suggestion,
                    'has_saved' => (bool) $record,
                ]);

                if ($suggestion) {
                    $suggestions[$student->id] = $suggestion;
                }
            }
        }

        return view('conduct.index', compact(
            'sections',
            'session',
            'classSectionId',
            'scopeType',
            'scopeKey',
            'students',
            'isPrimary',
            'suggestions'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeConductAccess();

        $institutionId = $this->getInstitutionId();
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'scope_type' => 'required|in:period,trimester,semester',
            'scope_key' => 'required|string|max:32',
            'conducts' => 'required|array',
            'conducts.*.conduct' => 'nullable|string|max:50',
            'conducts.*.notes' => 'nullable|string|max:500',
        ]);

        $section = ClassSection::with('gradeLevel')->findOrFail($request->class_section_id);
        if ((int) $section->institution_id !== (int) $institutionId) {
            abort(403);
        }
        $this->authorizeSectionAccess($section);

        $cycle = $section->gradeLevel?->education_cycle;
        $cycleValue = is_object($cycle) ? $cycle->value : (string) $cycle;
        $isPrimary = ! $this->cycleService->usesSemesterModel($cycleValue);

        // Secondary: only discipline-capable staff / admins (already checked in authorizeConductAccess for non-teachers)
        if (! $isPrimary) {
            $this->authorizeSecondaryConductEdit();
        }

        foreach ($request->input('conducts', []) as $studentId => $row) {
            $conduct = trim((string) ($row['conduct'] ?? ''));
            if ($conduct === '') {
                StudentConductRecord::query()
                    ->where('student_id', $studentId)
                    ->where('academic_session_id', $request->academic_session_id)
                    ->where('scope_type', $request->scope_type)
                    ->where('scope_key', (string) $request->scope_key)
                    ->delete();
                continue;
            }

            StudentConductRecord::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'academic_session_id' => $request->academic_session_id,
                    'scope_type' => $request->scope_type,
                    'scope_key' => (string) $request->scope_key,
                ],
                [
                    'institution_id' => $institutionId,
                    'conduct' => $conduct,
                    'notes' => $row['notes'] ?? null,
                    'entered_by' => Auth::id(),
                ]
            );
        }

        return $this->successResponse(
            __('conduct.saved'),
            route('conduct.index', [
                'class_section_id' => $request->class_section_id,
                'scope_type' => $request->scope_type,
                'scope_key' => $request->scope_key,
            ])
        );
    }

    private function authorizeConductAccess(): void
    {
        $user = Auth::user();
        if ($this->userIsSchoolAdmin($user)) {
            return;
        }

        if ($user->can('discipline.view') || $user->can('discipline.create') || $user->can('discipline.update')) {
            return;
        }

        // Class teachers may enter primary conduct
        if ($user->staff && ClassSection::where('staff_id', $user->staff->id)->exists()) {
            return;
        }

        abort(403, __('conduct.unauthorized'));
    }

    private function authorizeSecondaryConductEdit(): void
    {
        $user = Auth::user();
        if ($this->userIsSchoolAdmin($user)) {
            return;
        }
        if ($user->can('discipline.create') || $user->can('discipline.update') || $user->can('discipline.view')) {
            return;
        }
        abort(403, __('conduct.secondary_requires_discipline'));
    }

    private function authorizeSectionAccess(ClassSection $section): void
    {
        $user = Auth::user();
        if ($this->userIsSchoolAdmin($user)) {
            return;
        }
        if ($user->can('discipline.view') || $user->can('discipline.create') || $user->can('discipline.update')) {
            return;
        }
        if ($user->staff && (int) $section->staff_id === (int) $user->staff->id) {
            return;
        }
        abort(403);
    }

    private function allowedSections(int $institutionId)
    {
        $user = Auth::user();
        $query = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->orderBy('name');

        if (
            ! $this->userIsSchoolAdmin($user)
            && ! ($user->can('discipline.view') || $user->can('discipline.create') || $user->can('discipline.update'))
            && $user->staff
        ) {
            $query->where('staff_id', $user->staff->id);
        }

        return $query->get();
    }
}
