<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentRequest;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
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
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $query = StudentRequest::with(['student', 'staff'])
                ->where('institution_id', $institutionId)
                ->latest();

            // Filter for Students
            if ($user->hasRole(RoleEnum::STUDENT->value)) {
                $query->where('student_id', $user->student->id ?? 0);
            }
            // Filter for Teachers (Show their own requests OR requests from their students)
            elseif ($user->hasRole(RoleEnum::TEACHER->value)) {
                // If staff wants to see their OWN leave requests + their students' requests
                $staffId = $user->staff->id ?? 0;
                $query->where(function($q) use ($staffId) {
                    $q->where('staff_id', $staffId) // Own requests
                      ->orWhereHas('student.enrollments.classSection', function($sq) use ($staffId) {
                          $sq->where('staff_id', $staffId); // Students in their class
                      });
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('applicant', function($row){
                    return $row->student ? $row->student->full_name . ' (Student)' : ($row->staff ? $row->staff->user->name . ' (Staff)' : 'N/A');
                })
                ->editColumn('type', function($row){
                    return __('requests.type_' . $row->type);
                })
                ->editColumn('status', function($row){
                    $badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    return '<span class="badge badge-' . ($badges[$row->status] ?? 'secondary') . '">' . __('requests.status_' . $row->status) . '</span>';
                })
                ->editColumn('created_at', function($row){
                    return $row->created_at->format('d M, Y');
                })
                ->addColumn('action', function($row) use ($user) {
                    $btn = '<div class="d-flex">';
                    $btn .= '<a href="'.route('requests.show', $row->id).'" class="btn btn-info btn-xs shadow me-1"><i class="fa fa-eye"></i></a>';
                    
                    // Admin Actions
                    if ($user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
                        if ($row->status === 'pending') {
                            $btn .= '<button class="btn btn-success btn-xs shadow me-1 approve-btn" data-id="'.$row->id.'"><i class="fa fa-check"></i></button>';
                            $btn .= '<button class="btn btn-danger btn-xs shadow reject-btn" data-id="'.$row->id.'"><i class="fa fa-times"></i></button>';
                        }
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('requests.index');
    }

    public function create()
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();
        
        $students = [];
        $isStaff = $user->hasRole([RoleEnum::TEACHER->value, RoleEnum::STAFF->value]);
        $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);

        // If Admin, load ALL students for selection
        if ($isAdmin) {
            $students = Student::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->get()
                ->mapWithKeys(function($s) {
                    return [$s->id => $s->full_name . ' (' . $s->admission_number . ')'];
                });
        }
        
        return view('requests.create', compact('students', 'isStaff', 'isAdmin'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:absence,late,sick,early_exit,other,leave', // Added 'leave' for staff
            'reason' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'student_id' => 'nullable|exists:students,id' // Required if admin creating for student
        ]);

        $user = Auth::user();
        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        $filePath = null;
        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('requests', 'public');
        }

        // Determine Applicant
        $studentId = null;
        $staffId = null;

        if ($user->hasRole(RoleEnum::STUDENT->value)) {
            $studentId = $user->student->id;
        } elseif ($request->filled('student_id')) {
            // Admin/Staff creating for a student
            $studentId = $request->student_id;
        } elseif ($user->hasRole([RoleEnum::TEACHER->value, RoleEnum::STAFF->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
            // Staff creating for THEMSELVES (Leave)
            // Ensure Staff record exists
            $staffId = $user->staff->id ?? null;
            if (!$staffId && !$studentId) {
                // Fallback: If admin user doesn't have a linked 'staff' profile but wants to test
                // Ideally, admins should have staff profiles. 
                // For now, we allow only if student_id is set for proxy requests.
                 return back()->with('error', 'Please select a student or ensure your staff profile is active.');
            }
        }

        StudentRequest::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $session->id ?? 0,
            'student_id' => $studentId,
            'staff_id' => $staffId,
            'type' => $request->type,
            'reason' => $request->reason,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending', // Auto-approve logic can go here for admins
            'ticket_number' => 'REQ-' . strtoupper(Str::random(8)),
            'file_path' => $filePath
        ]);

        return redirect()->route('requests.index')->with('success', __('requests.success_create'));
    }

    public function show($id)
    {
        $request = StudentRequest::with(['student', 'staff'])->findOrFail($id);
        
        // Security check
        if ($this->getInstitutionId() != $request->institution_id) abort(403);
        
        return view('requests.show', compact('request'));
    }

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
            return response()->json(['message' => __('requests.unauthorized_action')], 403);
        }

        $req = StudentRequest::findOrFail($id);
        $req->update(['status' => $request->status]);

        return response()->json(['message' => __('requests.success_update')]);
    }
}