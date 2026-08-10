<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\GradeLevel;
use App\Models\PreEnrollment;
use App\Services\PreEnrollmentService;
use Illuminate\Http\Request;

class PreEnrollmentController extends BaseController
{
    public function __construct(
        protected PreEnrollmentService $preEnrollments
    ) {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $ok = $user && (
                $user->can('pre_enrollment.view')
                || $user->can('student.view')
                || $user->can('student_enrollment.view')
            );
            if (! $ok) {
                abort(403);
            }

            return $next($request);
        })->only(['index', 'show', 'create']);

        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $ok = $user && (
                $user->can('pre_enrollment.create')
                || $user->can('student.create')
            );
            if (! $ok) {
                abort(403);
            }

            return $next($request);
        })->only(['store', 'edit', 'update']);

        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $ok = $user && (
                $user->can('pre_enrollment.update')
                || $user->can('student.delete')
            );
            if (! $ok) {
                abort(403);
            }

            return $next($request);
        })->only(['destroy']);

        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $ok = $user && (
                $user->can('pre_enrollment.update')
                || $user->can('student.update')
                || $user->can('student.create')
            );
            if (! $ok) {
                abort(403);
            }

            return $next($request);
        })->only(['invite', 'remind', 'recordResult', 'finalize', 'togglePublicForm']);

        $this->setPageTitle(__('pre_enrollment.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (! $institutionId) {
            abort(403);
        }

        $status = $request->input('status', 'all');
        $query = PreEnrollment::with(['requestedGradeLevel', 'requestedClassSection', 'convertedStudent'])
            ->where('institution_id', $institutionId);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('temporary_id', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('parent_phone', 'like', "%{$q}%");
            });
        }

        $candidates = $query->latest('id')->paginate(25)->withQueryString();
        $stats = $this->preEnrollments->dashboardStats($institutionId);

        $notifications = app(\App\Services\NotificationService::class);
        $messagingReady = $notifications->channelEnabled($institutionId, 'pre_enrollment_received', 'whatsapp')
            || $notifications->channelEnabled($institutionId, 'pre_enrollment_received', 'sms');

        $institution = \App\Models\Institution::find($institutionId);
        $publicEnabled = in_array(
            (string) \App\Models\InstitutionSetting::get($institutionId, 'pre_enrollment_public_enabled', '1'),
            ['1', 'true', 'yes', 'on'],
            true
        );
        $publicUrl = $institution?->code
            ? route('public.pre-enrollments.create', $institution->code)
            : null;

        return view('pre_enrollments.index', compact(
            'candidates',
            'stats',
            'status',
            'messagingReady',
            'publicEnabled',
            'publicUrl',
            'institution'
        ));
    }

    public function togglePublicForm(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (! $institutionId) {
            abort(403);
        }

        $enabled = $request->boolean('enabled');
        \App\Models\InstitutionSetting::set(
            $institutionId,
            'pre_enrollment_public_enabled',
            $enabled ? '1' : '0',
            'enrollment'
        );

        return back()->with('success', $enabled
            ? __('pre_enrollment.public.enabled')
            : __('pre_enrollment.public.disabled'));
    }

    public function create()
    {
        return view('pre_enrollments.create', $this->formData() + ['candidate' => null]);
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $data = $this->validateCandidate($request);

        if (! $request->boolean('allow_duplicate')) {
            $duplicate = $this->preEnrollments->findDuplicate($institutionId, $data);
            if ($duplicate) {
                return back()
                    ->withInput()
                    ->with('error', __('pre_enrollment.errors.duplicate', ['id' => $duplicate->temporary_id]));
            }
        }

        $pre = $this->preEnrollments->register($data, $institutionId, 'admin');

        return redirect()->route('pre-enrollments.show', $pre)
            ->with('success', __('pre_enrollment.messages.created', ['id' => $pre->temporary_id]));
    }

    public function edit(PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);

        if ($pre_enrollment->status === PreEnrollment::STATUS_FINALIZED) {
            return redirect()->route('pre-enrollments.show', $pre_enrollment)
                ->with('error', __('pre_enrollment.errors.finalized_locked'));
        }

        return view('pre_enrollments.create', $this->formData() + ['candidate' => $pre_enrollment]);
    }

    public function update(Request $request, PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);
        $data = $this->validateCandidate($request);

        try {
            $this->preEnrollments->updateCandidate($pre_enrollment, $data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pre-enrollments.show', $pre_enrollment)
            ->with('success', __('pre_enrollment.messages.updated'));
    }

    public function destroy(PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);

        try {
            $this->preEnrollments->deleteCandidate($pre_enrollment);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pre-enrollments.index')
            ->with('success', __('pre_enrollment.messages.deleted'));
    }

    /** @return array<string, mixed> */
    protected function formData(): array
    {
        $institutionId = $this->getInstitutionId();
        $grades = GradeLevel::where('institution_id', $institutionId)->orderBy('order_index')->pluck('name', 'id');
        $classes = ClassSection::with('gradeLevel')->where('institution_id', $institutionId)->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->name . ' (' . ($c->gradeLevel->name ?? '') . ')']);
        $sessions = AcademicSession::where('institution_id', $institutionId)->orderByDesc('start_date')->pluck('name', 'id');

        return compact('grades', 'classes', 'sessions');
    }

    /** @return array<string, mixed> */
    protected function validateCandidate(Request $request): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'post_name' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date|before:today',
            'place_of_birth' => 'nullable|string|max:150',
            'parent_name' => 'nullable|string|max:150',
            'parent_phone' => 'nullable|string|max:40',
            'parent_email' => 'nullable|email|max:150',
            'requested_grade_level_id' => 'nullable|exists:grade_levels,id',
            'requested_class_section_id' => 'nullable|exists:class_sections,id',
            'requested_option' => 'nullable|string|max:100',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    public function show(PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);
        $pre_enrollment->load(['requestedGradeLevel', 'requestedClassSection', 'convertedStudent', 'academicSession']);

        $institutionId = $pre_enrollment->institution_id;
        $classes = ClassSection::with('gradeLevel')->where('institution_id', $institutionId)->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->name . ' (' . ($c->gradeLevel->name ?? '') . ')']);
        $sessions = AcademicSession::where('institution_id', $institutionId)->orderByDesc('start_date')->pluck('name', 'id');

        return view('pre_enrollments.show', [
            'candidate' => $pre_enrollment,
            'classes' => $classes,
            'sessions' => $sessions,
        ]);
    }

    public function invite(Request $request, PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);
        $data = $request->validate([
            'test_at' => 'required|date',
            'test_location' => 'nullable|string|max:255',
            'test_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->preEnrollments->inviteForTest($pre_enrollment, $data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('pre_enrollment.messages.invited'));
    }

    public function remind(PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);

        try {
            $this->preEnrollments->sendTestReminder($pre_enrollment);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('pre_enrollment.messages.reminded'));
    }

    public function recordResult(Request $request, PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);
        $data = $request->validate([
            'test_result' => 'required|in:pass,fail',
            'test_score' => 'nullable|numeric|min:0|max:1000',
            'test_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->preEnrollments->recordTestResult($pre_enrollment, $data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('pre_enrollment.messages.result_saved'));
    }

    public function finalize(Request $request, PreEnrollment $pre_enrollment)
    {
        $this->assertInstitution($pre_enrollment->institution_id);
        $data = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date|before:today',
            'force' => 'nullable|boolean',
        ]);

        try {
            $student = $this->preEnrollments->finalizeEnrollment($pre_enrollment, $data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('students.show', $student)
            ->with('success', __('pre_enrollment.messages.finalized', [
                'temp' => $pre_enrollment->temporary_id,
                'id' => $student->admission_number,
            ]));
    }

    protected function assertInstitution(?int $institutionId): void
    {
        $active = $this->getInstitutionId();
        if ($active && (int) $institutionId !== (int) $active) {
            abort(403);
        }
    }
}
