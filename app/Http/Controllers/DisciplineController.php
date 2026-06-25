<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\DisciplinaryRecord;
use App\Models\Student;
use App\Models\StudentParent;
use App\Enums\RoleEnum;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DisciplineController extends BaseController
{
    public function __construct(
        protected NotificationService $notificationService,
        protected InAppNotificationService $inAppNotifications
    ) {
        $this->middleware('auth');
        $this->setPageTitle(__('discipline.page_title'));
    }

    public function index(Request $request)
    {
        $this->authorizeDisciplineAccess('view');
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if ($request->ajax()) {
            $query = DisciplinaryRecord::with(['student', 'recorder'])->latest();

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            } elseif ($this->userIsSchoolAdmin($user)) {
                $allowed = $this->getAllowedInstitutionIds($user);
                if ($allowed !== []) {
                    $query->whereIn('institution_id', $allowed);
                }
            } else {
                abort(403, __('configuration.institution_not_found'));
            }

            if ($user->hasRole(RoleEnum::STUDENT->value)) {
                $query->where('student_id', $user->student->id ?? 0);
            } elseif ($user->hasRole(RoleEnum::GUARDIAN->value)) {
                $parent = StudentParent::where('user_id', $user->id)->first();
                $childIds = $parent
                    ? Student::where('parent_id', $parent->id)->pluck('id')
                    : collect();
                $query->whereIn('student_id', $childIds);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            if ($request->filled('incident_type') && $request->incident_type !== 'all') {
                $query->where('incident_type', $request->incident_type);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('reference', fn ($row) => '<span class="fw-bold text-primary">' . e($row->reference_no) . '</span>')
                ->addColumn('student_name', fn ($row) => e($row->student->full_name ?? 'N/A'))
                ->addColumn('incident_type_label', fn ($row) => e($row->typeLabel()))
                ->editColumn('incident_date', fn ($row) => $row->incident_date->format('d M, Y'))
                ->editColumn('severity', fn ($row) => '<span class="badge badge-' . ($row->severity === 'major' ? 'danger' : ($row->severity === 'moderate' ? 'warning' : 'info')) . '">' . e($row->severityLabel()) . '</span>')
                ->editColumn('status', fn ($row) => '<span class="badge badge-' . ($row->status === 'active' ? 'warning' : ($row->status === 'resolved' ? 'success' : 'secondary')) . '">' . e($row->statusLabel()) . '</span>')
                ->addColumn('action', function ($row) use ($user) {
                    $btn = '<div class="d-flex justify-content-end">';
                    $btn .= '<a href="' . route('discipline.show', $row->id) . '" class="btn btn-info btn-sm shadow me-1"><i class="fa fa-eye"></i></a>';
                    if ($user->can('update', $row)) {
                        $btn .= '<button type="button" class="btn btn-primary btn-sm shadow update-status-btn me-1" data-id="' . $row->id . '" data-status="' . $row->status . '"><i class="fa fa-cogs"></i></button>';
                    }
                    if ($user->can('delete', $row)) {
                        $btn .= '<button type="button" class="btn btn-danger btn-sm shadow delete-btn" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['reference', 'severity', 'status', 'action'])
                ->make(true);
        }

        return view('discipline.index');
    }

    public function create()
    {
        $this->authorizeDisciplineAccess('create');
        $institutionId = $this->requireInstitutionId();

        $students = Student::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->full_name . ' (' . ($s->admission_number ?? $s->id) . ')']);

        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        return view('discipline.create', compact('students', 'session'));
    }

    public function store(Request $request)
    {
        $this->authorizeDisciplineAccess('create');
        $institutionId = $this->requireInstitutionId();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'incident_type' => 'required|in:' . implode(',', DisciplinaryRecord::TYPES),
            'severity' => 'required|in:' . implode(',', DisciplinaryRecord::SEVERITIES),
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'action_taken' => 'nullable|string|max:2000',
            'incident_date' => 'required|date|before_or_equal:today',
            'notify_parents' => 'nullable|boolean',
        ]);

        $student = Student::where('institution_id', $institutionId)->findOrFail($validated['student_id']);
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        $record = DisciplinaryRecord::create([
            'institution_id' => $institutionId,
            'student_id' => $student->id,
            'academic_session_id' => $session?->id,
            'recorded_by' => Auth::id(),
            'incident_type' => $validated['incident_type'],
            'severity' => $validated['severity'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'action_taken' => $validated['action_taken'] ?? null,
            'incident_date' => $validated['incident_date'],
            'notify_parents' => $request->boolean('notify_parents', true),
            'status' => 'active',
        ]);

        try {
            if ($record->notify_parents) {
                $this->notificationService->sendDisciplinaryIncidentNotification($record);
            }
            $this->inAppNotifications->notifyDisciplinaryIncident($record);
        } catch (\Exception $e) {
            Log::error('Discipline notification error: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('discipline.success_created')]);
        }

        return redirect()->route('discipline.index')->with('success', __('discipline.success_created'));
    }

    public function show(DisciplinaryRecord $discipline)
    {
        $this->authorizeDisciplineAccess('view', $discipline);
        $discipline->load(['student.parent', 'recorder', 'academicSession']);

        return view('discipline.show', ['record' => $discipline]);
    }

    public function updateStatus(Request $request, DisciplinaryRecord $discipline)
    {
        $this->authorizeDisciplineAccess('update', $discipline);

        $request->validate([
            'status' => 'required|in:' . implode(',', DisciplinaryRecord::STATUSES),
            'action_taken' => 'nullable|string|max:2000',
        ]);

        $discipline->update([
            'status' => $request->status,
            'action_taken' => $request->filled('action_taken') ? $request->action_taken : $discipline->action_taken,
        ]);

        return response()->json(['message' => __('discipline.success_updated')]);
    }

    public function destroy(DisciplinaryRecord $discipline)
    {
        $this->authorizeDisciplineAccess('delete', $discipline);
        $discipline->delete();

        return response()->json(['message' => __('discipline.success_deleted')]);
    }

    private function authorizeDisciplineAccess(string $action, ?DisciplinaryRecord $record = null): void
    {
        $permissionMap = [
            'view' => ['discipline.view', 'discipline.viewAny'],
            'create' => ['discipline.create'],
            'update' => ['discipline.update'],
            'delete' => ['discipline.delete'],
        ];

        $this->authorizeAdminOrAnyPermission($permissionMap[$action] ?? ['discipline.view']);

        if ($record) {
            $this->assertInstitutionMatch((int) $record->institution_id);
        }
    }

    private function requireInstitutionId(): int
    {
        $institutionId = $this->getInstitutionId();
        if (!$institutionId) {
            abort(403, __('configuration.institution_not_found'));
        }

        return (int) $institutionId;
    }
}
