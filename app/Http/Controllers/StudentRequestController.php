<?php

namespace App\Http\Controllers;

use App\Models\StudentRequest;
use App\Models\Student;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\RoleEnum;
use App\Services\NotificationService;
use App\Services\StudentRequestContextService;
use App\Services\StudentRequestNotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StudentRequestController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->setPageTitle(__('requests.page_title') ?? 'Requests & Applications');
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if ($request->ajax()) {
            $query = StudentRequest::with(['student.parent', 'student.enrollments.classSection.gradeLevel'])
                ->select('student_requests.*')
                ->where('student_requests.institution_id', $institutionId)
                ->whereNotNull('student_requests.student_id')
                ->latest('student_requests.created_at');

            // Student: View Own
            if ($user->hasRole(RoleEnum::STUDENT->value)) {
                $query->where('student_requests.student_id', $user->student->id ?? 0);
            }
            
            // Teacher: NO VIEW
            if ($user->hasRole(RoleEnum::TEACHER->value)) {
                $query->whereRaw('1 = 0');
            }

            // Server-side Dropdown Filter Applied
            if ($request->filled('status') && $request->status !== 'all') {
                if ($request->status === 'pending') {
                    $query->whereIn('student_requests.status', ['pending', 'submitted']);
                } else {
                    $query->where('student_requests.status', $request->status);
                }
            }

            if ($request->filled('class_section_id')) {
                $classSectionId = (int) $request->class_section_id;
                $query->whereHas('student.enrollments', function ($q) use ($classSectionId) {
                    $q->where('status', 'active')->where('class_section_id', $classSectionId);
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('applicant', fn ($row) => request_applicant_html($row->student))
                ->addColumn('classe', function ($row) {
                    $enrollment = $row->student?->enrollments
                        ?->where('status', 'active')
                        ->sortByDesc('id')
                        ->first();

                    return e(class_section_short_label($enrollment?->classSection));
                })
                ->addColumn('deadline', function ($row) {
                    $deadline = $row->payment_deadline ?? $row->end_date;
                    return $deadline ? localized_date($deadline, 'd M Y') : '—';
                })
                ->addColumn('student_name', fn($row) => $row->student->full_name ?? 'N/A')
                ->addColumn('ticket', fn($row) => '<span class="fw-bold">'.$row->ticket_number.'</span>')
                ->editColumn('type', fn ($row) => $row->typeLabel())
                ->editColumn('created_at', fn($row) => $row->created_at ? localized_date($row->created_at, 'd M Y H:i') : '-')
                ->editColumn('status', function($row){
                    $badges = [
                        'submitted' => 'warning', 'pending' => 'warning', 'under_review' => 'info',
                        'approved' => 'success', 'partially_approved' => 'info', 'rejected' => 'danger',
                        'additional_info_required' => 'secondary',
                        'honored' => 'success', 'expired' => 'dark',
                    ];
                    $statusClass = $badges[$row->status] ?? 'secondary';
                    $statusText = __('requests.status_' . $row->status);
                    
                    // Fallback string if translation is missing
                    if ($statusText === 'requests.status_' . $row->status) {
                        $statusText = ucfirst(str_replace('_', ' ', $row->status));
                    }
                    
                    return '<span class="badge badge-' . $statusClass . '">' . $statusText . '</span>';
                })
                ->addColumn('action', function($row) use ($user) {
                    $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);
                    
                    $btn = '<div class="d-flex justify-content-end">';
                    
                    if ($isAdmin) {
                        // Modal Trigger Button for Processing
                        $btn .= '<button type="button" class="btn btn-sm btn-primary shadow-sm update-status me-1" 
                                    data-id="'.$row->id.'" 
                                    data-ticket="'.$row->ticket_number.'"
                                    data-student="'.htmlspecialchars($row->student->full_name ?? 'N/A').'"
                                    data-reason="'.htmlspecialchars($row->localizedReason()).'"
                                    data-status="'.$row->status.'"
                                    data-type="'.$row->resolvedType().'"
                                    data-note="'.htmlspecialchars($row->admin_note ?? '').'"><i class="fa fa-cogs"></i></button>';
                    }
                    
                    $btn .= '<a href="'.route('requests.show', $row->id).'" class="btn btn-info btn-sm shadow me-1"><i class="fa fa-eye"></i></a>';
                    
                    if ($isAdmin) {
                        $btn .= '<button class="btn btn-danger btn-sm shadow delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['ticket', 'status', 'action', 'applicant'])
                ->make(true);
        }

        $classes = \App\Models\ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('requests.index', compact('classes'));
    }

    public function create()
    {
        $user = Auth::user();
        
        // Strict Check: Teacher cannot create generic requests
        if ($user->hasRole(RoleEnum::TEACHER->value)) {
            abort(403, __('requests.unauthorized_teacher'));
        }

        $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);
        $isStaff = $user->hasRole(RoleEnum::TEACHER->value) || $user->staff !== null;
        
        $students = [];

        if ($isAdmin) {
            $students = Student::where('institution_id', $this->getInstitutionId())
                ->where('status', 'active')
                ->get()
                ->mapWithKeys(fn($s) => [$s->id => $s->full_name . ' (' . $s->admission_number . ')']);
        }

        return view('requests.create', compact('isAdmin', 'students', 'isStaff'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->hasRole(RoleEnum::TEACHER->value)) {
            abort(403, __('requests.unauthorized_teacher'));
        }

        $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);
        $institutionId = $this->getInstitutionId();

        if (!$institutionId || $institutionId === 'global') {
            return $this->errorResponse(__('requests.no_institution_context'));
        }

        $request->validate([
            'student_id' => $isAdmin ? 'required|exists:students,id' : 'nullable',
            'type' => 'required|in:' . implode(',', StudentRequest::STUDENT_TYPES),
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'days' => 'nullable|integer|min:1|max:365',
            'reason' => 'required|string|max:5000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048'
        ]);

        $studentId = $isAdmin ? $request->student_id : ($user->student?->id);

        if (!$studentId) {
            return $this->errorResponse(__('requests.student_required'), 422, [
                'student_id' => [__('requests.student_required')],
            ]);
        }

        if ($isAdmin) {
            Student::where('institution_id', $institutionId)->findOrFail($studentId);
        }
        
        $session = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first()
            ?? AcademicSession::where('institution_id', $institutionId)
                ->orderByDesc('start_date')
                ->first();

        if (!$session) {
            return $this->errorResponse(__('requests.no_current_session'));
        }
        
        $path = null;
        if($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('requests', 'public');
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : null;
        $days = $request->filled('days') ? (int) $request->days : null;
        if ($days && !$endDate) {
            $endDate = $startDate->copy()->addDays($days);
        } elseif ($startDate && $endDate && !$days) {
            $days = max(1, $startDate->diffInDays($endDate));
        }

        $paymentDeadline = null;
        if ($request->type === 'fee_extension' && $days) {
            $paymentDeadline = now()->startOfDay()->addDays($days);
        }

        try {
            $createdRequest = StudentRequest::create([
                'institution_id' => $institutionId,
                'student_id' => $studentId,
                'academic_session_id' => $session->id,
                'type' => $request->type,
                'reason' => $request->reason,
                'reason_params' => $days ? ['days' => $days] : null,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate?->toDateString(),
                'payment_deadline' => $paymentDeadline?->toDateString(),
                'status' => $isAdmin ? 'approved' : 'submitted',
                'ticket_number' => 'REQ-' . strtoupper(Str::random(8)),
                'created_by' => $user->id,
                'approved_by' => $isAdmin ? $user->id : null,
                'approved_at' => $isAdmin ? now() : null,
                'file_path' => $path
            ]);
        } catch (\Throwable $e) {
            Log::error('Student request create failed', [
                'message' => $e->getMessage(),
                'institution_id' => $institutionId,
                'student_id' => $studentId,
                'user_id' => $user->id,
            ]);

            return $this->errorResponse(__('requests.create_failed'), 500);
        }

        if (in_array($createdRequest->status, ['pending', 'submitted'], true)) {
            try {
                app(StudentRequestNotificationDispatcher::class)->onSubmitted($createdRequest);
            } catch (\Throwable $e) {
                Log::error('Student request submit notification failed: ' . $e->getMessage());
            }
        }

        return $this->successResponse(__('requests.success_create'), route('requests.index'));
    }

    public function show($id)
    {
        $request = StudentRequest::with(['student.parent', 'creator', 'approver'])->findOrFail($id);
        if ($this->getInstitutionId() != $request->institution_id) abort(403);

        $dossier = $request->student
            ? app(StudentRequestContextService::class)->buildDossier($request->student, $request->academic_session_id)
            : null;
        
        return view('requests.show', compact('request', 'dossier'));
    }

    public function dossier($id)
    {
        $studentRequest = StudentRequest::with('student')->findOrFail($id);
        if ($this->getInstitutionId() != $studentRequest->institution_id) abort(403);

        if (!$studentRequest->student) {
            return response()->json(['html' => '']);
        }

        $dossier = app(StudentRequestContextService::class)->buildDossier(
            $studentRequest->student,
            $studentRequest->academic_session_id
        );

        return response()->json([
            'html' => view('requests.partials.dossier', compact('dossier'))->render(),
        ]);
    }

    /**
     * Process the Request and Notify the Parent
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
            return response()->json(['message' => __('requests.unauthorized_action') ?? 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:under_review,approved,partially_approved,rejected,additional_info_required',
            'admin_note' => 'nullable|string|max:500',
            'approved_days' => 'nullable|integer|min:1',
            'payment_deadline' => 'nullable|date|after_or_equal:today',
        ]);

        $institutionId = $this->getInstitutionId();
        $studentRequest = StudentRequest::with('student.parent')->where('institution_id', $institutionId)->findOrFail($id);

        $studentRequest->status = $request->status;
        $studentRequest->admin_note = $request->admin_note;
        $studentRequest->approved_by = $user->id;
        $studentRequest->approved_at = now();

        if ($request->status === 'partially_approved' && $request->filled('approved_days')) {
            $days = (int) $request->approved_days;
            $studentRequest->end_date = Carbon::parse($studentRequest->start_date)->addDays($days);
            $params = is_array($studentRequest->reason_params) ? $studentRequest->reason_params : [];
            $params['days'] = $days;
            $studentRequest->reason_params = $params;
        }

        if (in_array($request->status, ['approved', 'partially_approved'], true)
            && $studentRequest->resolvedType() === 'fee_extension') {
            if ($request->filled('payment_deadline')) {
                $studentRequest->payment_deadline = $request->payment_deadline;
            } elseif ($request->filled('approved_days')) {
                $studentRequest->payment_deadline = now()->startOfDay()->addDays((int) $request->approved_days)->toDateString();
            } elseif (!$studentRequest->payment_deadline && $studentRequest->end_date) {
                $studentRequest->payment_deadline = Carbon::parse($studentRequest->end_date)->toDateString();
            }
        }

        $studentRequest->save();

        try {
            app(StudentRequestNotificationDispatcher::class)->onUpdated($studentRequest);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Request Notification Error: " . $e->getMessage());
        }

        return response()->json(['message' => __('requests.success_update') ?? 'Successfully updated']);
    }

    public function destroy($id)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        $req = StudentRequest::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->findOrFail($id);

        $isAdmin = $user->hasRole([
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::SCHOOL_ADMIN->value,
        ]);

        if (!$isAdmin) {
            $studentId = $user->student->id ?? null;
            if (!$studentId || (int) $req->student_id !== (int) $studentId) {
                abort(403);
            }
            if ($req->status !== 'submitted' && $req->status !== 'pending') {
                abort(403);
            }
        }

        $req->delete();
        return response()->json(['message' => __('requests.success_delete') ?? 'Deleted']);
    }

    /**
     * Build and Dispatch the WhatsApp/SMS Notification using Templates
     */
    private function notifyParent(StudentRequest $req)
    {
        if ($this->notificationService) {
            // Log::info("Attempting to send notification for Request ID: " . $req->id);
            // 1. Prioritize using the dedicated notification service method if you added it
            if (method_exists($this->notificationService, 'sendRequestUpdatedNotification')) {
                $this->notificationService->sendRequestUpdatedNotification($req);
                Log::info("Notification sent via dedicated service method for Request ID: " . $req->id);
                return;
            }
            Log::info("Dedicated service method not found, falling back to manual notification for Request ID: " . $req->id);
            // 2. Fallback: Assemble the data and pass it to the Database Template Engine
            $student = $req->student;
            if (!$student) return;

            $parent = $student->parent;
            $phone = $parent ? ($parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone) : $student->mobile_number;

            if (!$phone) return;

            $statusText = __('requests.status_' . $req->status);
            if ($statusText === 'requests.status_' . $req->status) {
                $statusText = ucfirst(str_replace('_', ' ', $req->status));
            }

            $typeText = $req->typeLabel();

            $data = [
                'StudentName' => $student->first_name,
                'TicketNumber' => $req->ticket_number,
                'Type' => $typeText,
                'Status' => $statusText,
                'Note' => $req->admin_note ?? 'N/A',
            ];

            // This hits NotificationService -> sendNotificationEvent -> Fetches `request_updated` from DB
            $this->notificationService->sendNotificationEvent('request_updated', $phone, $data, $req->institution_id, 'whatsapp');
            $this->notificationService->sendNotificationEvent('request_updated', $phone, $data, $req->institution_id, 'sms');
        }
    }
}