<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ParentMeeting;
use App\Models\Student;
use App\Models\StudentParent;
use App\Services\InAppNotificationService;
use App\Services\Mobile\MobileActiveRoleService;
use App\Services\ParentMeetingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ParentMeetingApiController extends Controller
{
    public function __construct(
        protected MobileActiveRoleService $activeRoles,
        protected ParentMeetingNotificationService $ptmNotifications,
        protected InAppNotificationService $inApp,
    ) {}

    private function isPortalRole($user): bool
    {
        return $this->activeRoles->userActsAs($user, ['Student', 'Guardian']);
    }

    private function isStaff($user): bool
    {
        if ($this->isPortalRole($user)) {
            return false;
        }

        return $user->hasRole([
            'Teacher',
            'School Admin',
            'Head Officer',
            'Super Admin',
            'Accountant',
            'Receptionist',
            'Staff',
        ]) || $user->staff !== null || $user->can('student.view');
    }

    private function guardianChildIds($user): array
    {
        $parent = StudentParent::where('user_id', $user->id)->first();
        if (!$parent) {
            return [];
        }

        return Student::where('parent_id', $parent->id)->pluck('id')->all();
    }

    private function resolveStudentForCreate(Request $request, $user): Student
    {
        $studentId = $request->input('student_id');

        if ($this->activeRoles->userActsAs($user, 'Student') || $user->hasRole('Student')) {
            $student = $user->student;
            if (!$student) {
                abort(404, 'Student profile missing.');
            }
            if ($studentId && (int) $studentId !== (int) $student->id) {
                abort(403, 'Unauthorized student.');
            }

            return $student;
        }

        if ($this->activeRoles->userActsAs($user, 'Guardian') || $user->hasRole('Guardian')) {
            $childIds = $this->guardianChildIds($user);
            if (empty($childIds)) {
                abort(404, 'No children linked to this account.');
            }

            if ($studentId) {
                if (!in_array((int) $studentId, array_map('intval', $childIds), true)) {
                    abort(403, 'Unauthorized student.');
                }

                return Student::findOrFail($studentId);
            }

            return Student::findOrFail($childIds[0]);
        }

        abort(403, 'Only parents or students can request a PTM from mobile. Staff schedule meetings on the web.');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ParentMeeting::with(['student', 'classSection.gradeLevel'])
            ->orderByDesc('preferred_date')
            ->orderByDesc('id');

        if ($this->isStaff($user)) {
            if ($user->institute_id) {
                $query->where('institution_id', $user->institute_id);
            }
            if ($request->filled('student_id')) {
                $query->where('student_id', (int) $request->query('student_id'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }
        } elseif ($this->activeRoles->userActsAs($user, 'Guardian') || $user->hasRole('Guardian')) {
            $childIds = $this->guardianChildIds($user);
            if (empty($childIds)) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $query->whereIn('student_id', $childIds);
            if ($request->filled('student_id')) {
                $sid = (int) $request->query('student_id');
                if (!in_array($sid, array_map('intval', $childIds), true)) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized student.'], 403);
                }
                $query->where('student_id', $sid);
            }
        } elseif ($this->activeRoles->userActsAs($user, 'Student') || $user->hasRole('Student')) {
            $student = $user->student;
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student profile missing.'], 404);
            }
            $query->where('student_id', $student->id);
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $items = $query->limit(100)->get()->map(fn (ParentMeeting $m) => $m->toApiArray());

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Mobile create is for parents/students only.
     * Staff schedule and manage PTMs on the web.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($this->isStaff($user) && !$this->isPortalRole($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule PTMs from the web admin. Mobile is for viewing.',
            ], 403);
        }

        $data = $request->validate([
            'student_id' => 'nullable|integer|exists:students,id',
            'topic' => 'required|string|max:200',
            'preferred_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:2000',
        ]);

        $student = $this->resolveStudentForCreate($request, $user);

        $meeting = ParentMeeting::create([
            'institution_id' => $student->institution_id,
            'scope' => 'individual',
            'student_id' => $student->id,
            'class_section_id' => $student->class_section_id,
            'requested_by' => $user->id,
            'topic' => $data['topic'],
            'preferred_date' => $data['preferred_date'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        $meeting->load(['student', 'classSection.gradeLevel', 'institution']);

        // Alert school staff that a parent/student requested a PTM.
        try {
            $this->inApp->notifyAdmins(
                $meeting->institution_id,
                ParentMeetingNotificationService::EVENT_KEY,
                'ptm',
                __('header.notif_ptm_title'),
                __('header.notif_ptm_message', [
                    'student' => $student->full_name,
                    'topic' => $meeting->topic,
                    'date' => $meeting->preferred_date?->format('d M Y') ?? '—',
                    'scope' => __('ptm.scope_individual'),
                ]),
                route('ptm.show', $meeting),
                'fa-users',
                ['parent_meeting_id' => $meeting->id],
                $user->id
            );
        } catch (\Throwable $e) {
            // Non-fatal — request still succeeds.
        }

        return response()->json([
            'success' => true,
            'message' => 'PTM requested.',
            'data' => $meeting->toApiArray(),
        ], 201);
    }

    /**
     * Status updates belong on web. Keep endpoint for compatibility but prefer web.
     */
    public function update(Request $request, int $id)
    {
        $user = Auth::user();

        if (!$this->isStaff($user)) {
            return response()->json(['success' => false, 'message' => 'Only staff can update meetings on web.'], 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(ParentMeeting::STATUSES)],
            'staff_notes' => 'nullable|string|max:2000',
        ]);

        $meeting = ParentMeeting::with('student')->findOrFail($id);

        if ($user->institute_id && (int) $meeting->institution_id !== (int) $user->institute_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $meeting->status = $data['status'];
        if (array_key_exists('staff_notes', $data)) {
            $meeting->staff_notes = $data['staff_notes'];
        }
        $meeting->handled_by = $user->id;
        $meeting->handled_at = now();
        $meeting->save();

        return response()->json([
            'success' => true,
            'message' => 'PTM updated.',
            'data' => $meeting->toApiArray(),
        ]);
    }
}
