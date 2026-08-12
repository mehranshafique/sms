<?php

namespace App\Http\Controllers;

use App\Models\ParentMeeting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ParentMeetingController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('ptm.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $status = $request->query('status');

        $baseQuery = ParentMeeting::query()
            ->with(['student', 'requester', 'handler'])
            ->when($institutionId && $institutionId !== 'global', fn ($q) => $q->where('institution_id', $institutionId));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        $meetings = (clone $baseQuery)
            ->when($status && in_array($status, ParentMeeting::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('preferred_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('ptm.index', compact('meetings', 'stats', 'status'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        $students = $this->studentOptions($institutionId);

        return view('ptm.create', compact('students'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'topic' => 'required|string|max:200',
            'preferred_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'staff_notes' => 'nullable|string|max:2000',
            'status' => ['nullable', Rule::in(ParentMeeting::STATUSES)],
        ]);

        $student = Student::where('institution_id', $institutionId)->findOrFail($data['student_id']);
        $status = $data['status'] ?? 'confirmed';

        $meeting = ParentMeeting::create([
            'institution_id' => $student->institution_id,
            'student_id' => $student->id,
            'requested_by' => Auth::id(),
            'handled_by' => Auth::id(),
            'topic' => $data['topic'],
            'preferred_date' => $data['preferred_date'],
            'notes' => $data['notes'] ?? null,
            'staff_notes' => $data['staff_notes'] ?? null,
            'status' => $status,
            'handled_at' => now(),
        ]);

        return $this->successResponse(__('ptm.created'), route('ptm.show', $meeting));
    }

    public function show(ParentMeeting $parentMeeting)
    {
        $this->assertInstitution($parentMeeting);
        $parentMeeting->load(['student', 'requester', 'handler']);

        return view('ptm.show', ['meeting' => $parentMeeting]);
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
        ]);

        $parentMeeting->fill(collect($data)->except('status')->all());
        $parentMeeting->status = $data['status'];
        $parentMeeting->handled_by = Auth::id();
        $parentMeeting->handled_at = now();
        $parentMeeting->save();

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
}
