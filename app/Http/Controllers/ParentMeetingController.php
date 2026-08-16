<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\ParentMeeting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\ParentMeetingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParentMeetingController extends BaseController
{
    public function __construct(
        protected ParentMeetingNotificationService $ptmNotifications
    ) {
        $this->middleware('auth');
        $this->setPageTitle(__('ptm.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $status = $request->query('status');
        $scope = $request->query('scope');

        $baseQuery = ParentMeeting::query()
            ->with(['student', 'requester', 'handler', 'classSection.gradeLevel'])
            ->when($institutionId && $institutionId !== 'global', fn ($q) => $q->where('institution_id', $institutionId));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'class' => (clone $baseQuery)->where('scope', 'class')->count(),
            'individual' => (clone $baseQuery)->where('scope', 'individual')->count(),
        ];

        $meetings = (clone $baseQuery)
            ->when($status && in_array($status, ParentMeeting::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($scope && in_array($scope, ParentMeeting::SCOPES, true), fn ($q) => $q->where('scope', $scope))
            ->orderByDesc('preferred_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('ptm.index', compact('meetings', 'stats', 'status', 'scope'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        $students = $this->studentOptions($institutionId);
        $sections = ClassSection::query()
            ->with('gradeLevel')
            ->when($institutionId && $institutionId !== 'global', fn ($q) => $q->where('institution_id', $institutionId))
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('ptm.create', compact('students', 'sections'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $data = $request->validate([
            'scope' => ['required', Rule::in(ParentMeeting::SCOPES)],
            'student_id' => 'nullable|integer|exists:students,id|required_if:scope,individual',
            'class_section_id' => 'nullable|integer|exists:class_sections,id|required_if:scope,class',
            'topic' => 'required|string|max:200',
            'preferred_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'staff_notes' => 'nullable|string|max:2000',
            'status' => ['nullable', Rule::in(ParentMeeting::STATUSES)],
            'notify' => 'nullable|boolean',
        ]);

        $status = $data['status'] ?? 'confirmed';
        // Checkbox: present when checked, absent when unchecked.
        $shouldNotify = $request->boolean('notify');

        if ($data['scope'] === 'individual') {
            $student = Student::where('institution_id', $institutionId)->findOrFail($data['student_id']);

            $meeting = ParentMeeting::create([
                'institution_id' => $student->institution_id,
                'scope' => 'individual',
                'student_id' => $student->id,
                'class_section_id' => $student->class_section_id,
                'batch_id' => null,
                'requested_by' => Auth::id(),
                'handled_by' => Auth::id(),
                'topic' => $data['topic'],
                'preferred_date' => $data['preferred_date'],
                'notes' => $data['notes'] ?? null,
                'staff_notes' => $data['staff_notes'] ?? null,
                'status' => $status,
                'handled_at' => now(),
            ]);

            if ($shouldNotify) {
                $this->safeNotifyCreated($meeting);
            }

            return $this->successResponse(__('ptm.created'), route('ptm.show', $meeting));
        }

        $section = ClassSection::where('institution_id', $institutionId)
            ->findOrFail($data['class_section_id']);

        $students = $this->studentsForClass((int) $institutionId, (int) $section->id);

        if ($students->isEmpty()) {
            return $this->errorResponse(__('ptm.no_students_in_class'), 422);
        }

        $batchId = (string) Str::uuid();
        $created = collect();

        DB::transaction(function () use ($students, $section, $data, $status, $batchId, &$created) {
            foreach ($students as $student) {
                $created->push(ParentMeeting::create([
                    'institution_id' => $student->institution_id,
                    'scope' => 'class',
                    'student_id' => $student->id,
                    'class_section_id' => $section->id,
                    'batch_id' => $batchId,
                    'requested_by' => Auth::id(),
                    'handled_by' => Auth::id(),
                    'topic' => $data['topic'],
                    'preferred_date' => $data['preferred_date'],
                    'notes' => $data['notes'] ?? null,
                    'staff_notes' => $data['staff_notes'] ?? null,
                    'status' => $status,
                    'handled_at' => now(),
                ]));
            }
        });

        if ($shouldNotify) {
            $this->safeNotifyCreated($created);
        }

        $first = $created->first();

        return $this->successResponse(
            __('ptm.created_class', ['count' => $created->count()]),
            $first ? route('ptm.show', $first) : route('ptm.index')
        );
    }

    public function show(ParentMeeting $parentMeeting)
    {
        $this->assertInstitution($parentMeeting);
        $parentMeeting->load(['student', 'requester', 'handler', 'classSection.gradeLevel']);

        $batchCount = null;
        if ($parentMeeting->batch_id) {
            $batchCount = ParentMeeting::where('batch_id', $parentMeeting->batch_id)->count();
        }

        return view('ptm.show', [
            'meeting' => $parentMeeting,
            'batchCount' => $batchCount,
        ]);
    }

    public function update(Request $request, ParentMeeting $parentMeeting)
    {
        $this->assertInstitution($parentMeeting);

        $data = $request->validate([
            'topic' => 'sometimes|required|string|max:200',
            'preferred_date' => 'sometimes|required|date',
            'notes' => 'nullable|string|max:2000',
            'staff_notes' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in(ParentMeeting::STATUSES)],
            'apply_to_batch' => 'nullable|boolean',
        ]);

        $applyBatch = $request->boolean('apply_to_batch') && $parentMeeting->batch_id;

        $targets = $applyBatch
            ? ParentMeeting::where('batch_id', $parentMeeting->batch_id)->get()
            : collect([$parentMeeting]);

        foreach ($targets as $meeting) {
            $meeting->fill(collect($data)->except(['status', 'apply_to_batch'])->all());
            $meeting->status = $data['status'];
            $meeting->handled_by = Auth::id();
            $meeting->handled_at = now();
            $meeting->save();
        }

        return $this->successResponse(__('ptm.updated'), route('ptm.show', $parentMeeting));
    }

    public function destroy(ParentMeeting $parentMeeting)
    {
        $this->assertInstitution($parentMeeting);
        $parentMeeting->delete();

        return $this->successResponse(__('ptm.deleted'), route('ptm.index'));
    }

    private function assertInstitution(ParentMeeting $meeting): void
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $institutionId !== 'global' && (int) $meeting->institution_id !== (int) $institutionId) {
            abort(403);
        }
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function studentOptions($institutionId)
    {
        return Student::query()
            ->when($institutionId && $institutionId !== 'global', fn ($q) => $q->where('institution_id', $institutionId))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($s) => [
                $s->id => $s->full_name . ' (' . ($s->admission_number ?? $s->id) . ')',
            ]);
    }

    /** @return \Illuminate\Support\Collection<int, Student> */
    private function studentsForClass(int $institutionId, int $classSectionId)
    {
        $sessionId = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');

        $fromEnrollments = StudentEnrollment::with('student')
            ->where('class_section_id', $classSectionId)
            ->where('status', 'active')
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereHas('student', fn ($q) => $q->where('institution_id', $institutionId)->where('status', 'active'))
            ->get()
            ->pluck('student')
            ->filter();

        if ($fromEnrollments->isNotEmpty()) {
            return $fromEnrollments->unique('id')->values();
        }

        return Student::where('institution_id', $institutionId)
            ->where('class_section_id', $classSectionId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @param  ParentMeeting|\Illuminate\Support\Collection<int, ParentMeeting>  $meetings
     */
    private function safeNotifyCreated($meetings): void
    {
        try {
            $this->ptmNotifications->notifyCreated($meetings);
        } catch (\Throwable $e) {
            Log::warning('PTM created but notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
