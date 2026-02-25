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

class StudentRequestController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('requests.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if ($request->ajax()) {
            // FIXED: Added select('student_requests.*') and explicit table prefixes to avoid "Ambiguous Column" errors during DataTables JOINs.
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

            return DataTables::of($query)
                ->addIndexColumn()
                // FIXED: Provided 'applicant' to match frontend JS, kept 'student_name' as a fallback just in case
                ->addColumn('applicant', fn($row) => $row->student->full_name ?? 'N/A')
                ->addColumn('student_name', fn($row) => $row->student->full_name ?? 'N/A')
                ->addColumn('ticket', fn($row) => '<span class="fw-bold">'.$row->ticket_number.'</span>')
                ->editColumn('type', fn($row) => __('requests.type_' . $row->type))
                ->editColumn('status', function($row){
                    $badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    return '<span class="badge badge-' . ($badges[$row->status] ?? 'secondary') . '">' . __('requests.status_' . $row->status) . '</span>';
                })
                ->addColumn('action', function($row) use ($user) {
                    $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);
                    
                    $btn = '<div class="d-flex justify-content-end">';
                    
                    if ($isAdmin && $row->status === 'pending') {
                        $btn .= '<button class="btn btn-success btn-xs shadow me-1 update-status" data-id="'.$row->id.'" data-status="approved"><i class="fa fa-check"></i></button>';
                        $btn .= '<button class="btn btn-danger btn-xs shadow me-1 update-status" data-id="'.$row->id.'" data-status="rejected"><i class="fa fa-times"></i></button>';
                    }
                    
                    $btn .= '<a href="'.route('requests.show', $row->id).'" class="btn btn-info btn-xs shadow me-1"><i class="fa fa-eye"></i></a>';
                    
                    if ($isAdmin) {
                        $btn .= '<button class="btn btn-danger btn-xs shadow delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
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
        
        // Strict Check: Teacher cannot create
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
        
        // Strict Check
        if ($user->hasRole(RoleEnum::TEACHER->value)) {
            abort(403, __('requests.unauthorized_teacher'));
        }

        $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);

        $request->validate([
            'student_id' => $isAdmin ? 'required|exists:students,id' : 'nullable',
            'type' => 'required|in:absence,late,sick,early_exit,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048'
        ]);

        $institutionId = $this->getInstitutionId();
        $studentId = $isAdmin ? $request->student_id : $user->student->id;
        
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->firstOrFail();
        
        $path = null;
        if($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('requests', 'public');
        }

        StudentRequest::create([
            'institution_id' => $institutionId,
            'student_id' => $studentId,
            'academic_session_id' => $session->id,
            'type' => $request->type,
            'reason' => $request->reason,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $isAdmin ? 'approved' : 'pending', // Auto-approve if admin
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

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
            return response()->json(['message' => __('requests.unauthorized')], 403);
        }

        $req = StudentRequest::findOrFail($id);
        $req->update([
            'status' => $request->status,
            'approved_by' => $user->id,
            'approved_at' => now()
        ]);

        return response()->json(['message' => __('requests.success_update')]);
    }

    public function destroy($id)
    {
        $req = StudentRequest::findOrFail($id);
        $req->delete();
        return response()->json(['message' => __('requests.success_delete')]);
    }
}