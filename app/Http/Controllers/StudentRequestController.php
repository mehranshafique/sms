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
            $query = StudentRequest::with(['student', 'creator'])
                ->select('student_requests.*')
                ->where('student_requests.institution_id', $institutionId)
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
                $query->where('student_requests.status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('applicant', fn($row) => $row->student->full_name ?? 'N/A')
                ->addColumn('student_name', fn($row) => $row->student->full_name ?? 'N/A')
                ->addColumn('ticket', fn($row) => '<span class="fw-bold">'.$row->ticket_number.'</span>')
                ->editColumn('type', fn($row) => __('requests.type_' . $row->type))
                ->editColumn('created_at', fn($row) => $row->created_at ? $row->created_at->format('d M, Y H:i') : '-') 
                ->editColumn('status', function($row){
                    $badges = ['pending' => 'warning', 'approved' => 'success', 'partially_approved' => 'info', 'rejected' => 'danger'];
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
                                    data-student="'.($row->student->full_name ?? 'N/A').'"
                                    data-reason="'.htmlspecialchars($row->reason).'"
                                    data-status="'.$row->status.'"
                                    data-note="'.htmlspecialchars($row->admin_note ?? '').'"><i class="fa fa-cogs"></i></button>';
                    }
                    
                    $btn .= '<a href="'.route('requests.show', $row->id).'" class="btn btn-info btn-sm shadow me-1"><i class="fa fa-eye"></i></a>';
                    
                    if ($isAdmin) {
                        $btn .= '<button class="btn btn-danger btn-sm shadow delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['ticket', 'status', 'action'])
                ->make(true);
        }

        return view('requests.index');
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

        $request->validate([
            'student_id' => $isAdmin ? 'required|exists:students,id' : 'nullable',
            'type' => 'required|in:absence,late,sick,early_exit,leave,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048'
        ]);

        $institutionId = $this->getInstitutionId();
        $studentId = $isAdmin ? $request->student_id : ($user->student->id ?? null);
        
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        $path = null;
        if($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('requests', 'public');
        }

        StudentRequest::create([
            'institution_id' => $institutionId,
            'student_id' => $studentId,
            'academic_session_id' => $session ? $session->id : null,
            'type' => $request->type,
            'reason' => $request->reason,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $isAdmin ? 'approved' : 'pending',
            'ticket_number' => 'REQ-' . strtoupper(Str::random(8)),
            'created_by' => $user->id,
            'approved_by' => $isAdmin ? $user->id : null,
            'approved_at' => $isAdmin ? now() : null,
            'file_path' => $path
        ]);

        return redirect()->route('requests.index')->with('success', __('requests.success_create'));
    }

    public function show($id)
    {
        $request = StudentRequest::with(['student', 'creator'])->findOrFail($id);
        if ($this->getInstitutionId() != $request->institution_id) abort(403);
        
        return view('requests.show', compact('request'));
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
            'status' => 'required|in:approved,partially_approved,rejected',
            'admin_note' => 'nullable|string|max:500',
            'approved_days' => 'nullable|integer|min:1'
        ]);

        $institutionId = $this->getInstitutionId();
        $studentRequest = StudentRequest::with('student.parent')->where('institution_id', $institutionId)->findOrFail($id);

        $studentRequest->status = $request->status;
        $studentRequest->admin_note = $request->admin_note;
        $studentRequest->approved_by = $user->id;
        $studentRequest->approved_at = now();

        if ($request->status === 'partially_approved' && $request->filled('approved_days')) {
            $studentRequest->end_date = Carbon::parse($studentRequest->start_date)->addDays((int) $request->approved_days);
        }

        $studentRequest->save();

        try {
            $this->notifyParent($studentRequest);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Request Notification Error: " . $e->getMessage());
        }

        return response()->json(['message' => __('requests.success_update') ?? 'Successfully updated']);
    }

    public function destroy($id)
    {
        $req = StudentRequest::findOrFail($id);
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

            $typeText = __('requests.type_' . $req->type);
            if ($typeText === 'requests.type_' . $req->type) {
                $typeText = ucfirst(str_replace('_', ' ', $req->type));
            }

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