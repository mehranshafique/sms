<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\AcademicSession;
use App\Models\Timetable;
use App\Models\ClassSubject; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Enums\RoleEnum;
use App\Jobs\NotifyHomeworkPublishedJob;
use App\Services\Academic\HomeworkApprovalService;
use App\Services\Academic\HomeworkNotificationService;
use Spatie\Permission\Middleware\PermissionMiddleware;

class AssignmentController extends BaseController
{
    public function __construct(
        protected HomeworkApprovalService $approvals,
        protected HomeworkNotificationService $homeworkNotifications
    ) {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':assignment.view')->only(['index', 'create', 'getSubjects']);
        $this->middleware(PermissionMiddleware::class . ':assignment.create')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':assignment.delete')->only(['destroy']);
        $this->middleware(PermissionMiddleware::class . ':assignment.approve')->only(['updateStatus']);
        $this->setPageTitle(__('assignment.page_title'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();

        $canApprove = $this->approvals->canApprove($user, $institutionId);

        if ($request->ajax()) {
            $query = Assignment::with(['classSection.gradeLevel', 'subject', 'teacher.user'])
                ->where('institution_id', $institutionId)
                ->latest();

            if ($user->hasRole(RoleEnum::TEACHER->value)) {
                $staff = $user->staff;
                if ($staff) {
                    $query->where('teacher_id', $staff->id);
                }
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return \Yajra\DataTables\Facades\DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    $html = dt_link(dt_route('assignments.show', $row->id, 'assignments.edit'), $row->title);
                    if ($row->file_path) {
                        $html .= ' <a href="'.asset('storage/'.$row->file_path).'" target="_blank" class="text-primary ms-1"><i class="fa fa-paperclip"></i></a>';
                    }
                    return $html;
                })
                ->addColumn('class_name', function ($row) {
                    $grade = $row->classSection->gradeLevel->name ?? '';
                    $section = $row->classSection->name ?? '';
                    $label = trim($grade.' '.$section);
                    $csId = $row->class_section_id ?? $row->classSection->id ?? null;
                    return dt_link(
                        $csId ? dt_route('class-sections.show', $csId, 'class-sections.edit') : null,
                        $label !== '' ? $label : null
                    );
                })
                ->editColumn('deadline', function ($row) {
                    $class = $row->deadline < now() ? 'danger' : 'success';
                    return '<span class="badge badge-'.$class.'">'.$row->deadline->format('d M, Y').'</span>';
                })
                ->addColumn('teacher_name', function ($row) {
                    $teacherId = $row->teacher_id ?? $row->teacher->id ?? null;
                    return dt_link(
                        $teacherId ? dt_route('staff.show', $teacherId, 'staff.edit') : null,
                        $row->teacher->user->name ?? 'Admin'
                    );
                })
                ->addColumn('status_badge', function ($row) {
                    $map = [
                        Assignment::STATUS_APPROVED => 'success',
                        Assignment::STATUS_PENDING => 'warning',
                        Assignment::STATUS_REJECTED => 'danger',
                    ];
                    $class = $map[$row->status] ?? 'secondary';
                    $label = __('assignment.status_' . $row->status);

                    $html = '<span class="badge badge-'.$class.'">'.e($label).'</span>';
                    if ($row->isRejected() && $row->rejection_reason) {
                        $html .= ' <i class="fa fa-info-circle text-muted" title="'.e($row->rejection_reason).'"></i>';
                    }

                    return $html;
                })
                ->addColumn('action', function ($row) use ($canApprove) {
                    $btn = '';

                    if ($canApprove && $row->isPending()) {
                        $btn .= '<button type="button" class="btn btn-success shadow btn-xs sharp me-1 approve-assignment-btn" data-id="'.$row->id.'" data-url="'.route('assignments.status', $row->id).'" title="'.e(__('assignment.approve')).'"><i class="fa fa-check"></i></button>';
                        $btn .= '<button type="button" class="btn btn-warning shadow btn-xs sharp me-1 reject-assignment-btn" data-id="'.$row->id.'" data-url="'.route('assignments.status', $row->id).'" title="'.e(__('assignment.reject')).'"><i class="fa fa-times"></i></button>';
                    }

                    if (auth()->user()->can('delete', $row) || auth()->user()->hasRole(['Super Admin', 'Head Officer'])) {
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-assignment-btn" data-id="'.$row->id.'" data-url="'.route('assignments.destroy', $row->id).'"><i class="fa fa-trash"></i></button>';
                    }

                    return $btn;
                })
                ->rawColumns(['title', 'class_name', 'teacher_name', 'deadline', 'status_badge', 'action'])
                ->make(true);
        }
        
        $query = Assignment::with(['classSection.gradeLevel', 'subject', 'teacher.user'])
            ->where('institution_id', $institutionId)
            ->latest();

        // Role-based filtering
        if ($user->hasRole(RoleEnum::STUDENT->value)) {
            $query->published();
            $student = $user->student;
            if ($student) {
                $currentClassId = $student->enrollments()
                    ->where('status', 'active')
                    ->latest('created_at')
                    ->value('class_section_id');
                
                if ($currentClassId) {
                    $query->where('class_section_id', $currentClassId);
                } else {
                    $query->whereRaw('1 = 0'); 
                }
            }
            // Return Specific Student View
            $assignments = $query->paginate(10);
            return view('assignments.student_index', compact('assignments'));
        }

        $pendingCount = Assignment::where('institution_id', $institutionId)
            ->awaitingApproval()
            ->count();

        $approvalRequired = $this->approvals->isRequired($institutionId);

        return view('assignments.index', compact('canApprove', 'pendingCount', 'approvalRequired'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        $classesQuery = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true);

        // UPDATE: Checked both Timetable AND ClassAllocation
        if ($user->hasRole(RoleEnum::TEACHER->value) && $user->staff) {
            $staffId = $user->staff->id;
            $classesQuery->where(function($q) use ($staffId) {
                $q->where('staff_id', $staffId) // Class Teacher
                  ->orWhereHas('timetables', function($t) use ($staffId) {
                      $t->where('teacher_id', $staffId); // Subject Teacher (Timetable)
                  })
                  ->orWhereHas('classSubjects', function($c) use ($staffId) {
                      $c->where('teacher_id', $staffId); // Subject Teacher (Allocation)
                  });
            });
        }

        $classes = $classesQuery->get()->mapWithKeys(function ($item) {
            $name = ($item->gradeLevel->name ?? '') . ' ' . $item->name;
            return [$item->id => $name];
        });

        $needsApproval = $this->approvals->isRequired($institutionId)
            && ! $this->approvals->canApprove($user, $institutionId);

        return view('assignments.create', compact('classes', 'needsApproval'));
    }

    // UPDATE: Fetch subjects based on hybrid logic
    public function getSubjects(Request $request)
    {
        $request->validate(['class_section_id' => 'required|exists:class_sections,id']);
        
        $user = Auth::user();
        $section = ClassSection::find($request->class_section_id);
        
        if (!$section) return response()->json([]);

        if ($user->hasRole(RoleEnum::TEACHER->value) && $user->staff) {
            $staffId = $user->staff->id;
            
            // 1. Get from Timetable
            $subjectIds = Timetable::where('teacher_id', $staffId)
                ->where('class_section_id', $section->id)
                ->pluck('subject_id')
                ->toArray();

            // 2. Get from Allocations
            $allocatedIds = ClassSubject::where('teacher_id', $staffId)
                ->where('class_section_id', $section->id)
                ->pluck('subject_id')
                ->toArray();

            $allIds = array_unique(array_merge($subjectIds, $allocatedIds));

            if (empty($allIds)) return response()->json([]);

            $subjects = Subject::whereIn('id', $allIds)->where('is_active', true)->get();
        } else {
            // Admin logic: Prefer allocated subjects if exist, else Global
            $allocated = ClassSubject::where('class_section_id', $section->id)->with('subject')->get();
            if ($allocated->isNotEmpty()) {
                $subjects = $allocated->pluck('subject');
            } else {
                $subjects = Subject::where('grade_level_id', $section->grade_level_id)
                    ->where('is_active', true)
                    ->get();
            }
        }

        $formatted = $subjects->map(function($s) {
            return ['id' => $s->id, 'name' => $s->name];
        });

        return response()->json($formatted);
    }

    // ... (Store and destroy methods remain unchanged) ...
    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'deadline' => 'required|date|after_or_equal:today',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if (!$session) {
            return $this->errorResponse(__('assignment.no_active_session'));
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('assignments', 'public');
        }

        $user = Auth::user();

        $assignment = Assignment::create(array_merge([
            'institution_id' => $institutionId,
            'academic_session_id' => $session->id,
            'class_section_id' => $request->class_section_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $user->staff->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'file_path' => $filePath,
        ], $this->approvals->attributesForNew($user, $institutionId)));

        if ($assignment->isPublished()) {
            NotifyHomeworkPublishedJob::dispatchAfterResponse($assignment->id);

            return $this->successResponse(__('assignment.success_create'), route('assignments.index'));
        }

        $this->homeworkNotifications->notifyAwaitingApproval($assignment);

        return $this->successResponse(__('assignment.success_create_pending'), route('assignments.index'));
    }

    /**
     * Approve or reject homework that is waiting for review.
     */
    public function updateStatus(Request $request, Assignment $assignment)
    {
        $institutionId = $this->getInstitutionId();

        if ($institutionId && (int) $assignment->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $user = Auth::user();

        if (! $this->approvals->canApprove($user, $assignment->institution_id)) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($request->status === Assignment::STATUS_APPROVED) {
            $this->approvals->approve($assignment, $user);
            NotifyHomeworkPublishedJob::dispatchAfterResponse($assignment->id);
            $message = __('assignment.success_approved');
        } else {
            $this->approvals->reject($assignment, $user, $request->rejection_reason);
            $message = __('assignment.success_rejected');
        }

        $this->homeworkNotifications->notifyTeacherDecision($assignment->refresh());

        if ($request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
    
    public function destroy(Assignment $assignment)
    {
        if ($assignment->file_path) {
            Storage::disk('public')->delete($assignment->file_path);
        }
        $assignment->delete();

        // Support AJAX SweetAlert delete
        if (request()->ajax()) {
            return response()->json(['message' => __('assignment.success_delete')]);
        }

        return back()->with('success', __('assignment.success_delete'));
    }
}