<?php

namespace App\Http\Controllers;

use App\Models\StudentAttendance;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\Permission\Middleware\PermissionMiddleware;

class StudentAttendanceController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':student_attendance.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':student_attendance.create')->only(['create', 'store']);
        
        $this->setPageTitle(__('attendance.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        if ($request->ajax()) {
            $data = StudentAttendance::with(['student', 'classSection'])
                ->select('student_attendances.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            if ($request->filled('class_section_id')) {
                $data->where('class_section_id', $request->class_section_id);
            }
            if ($request->filled('attendance_date')) {
                $data->whereDate('attendance_date', $request->attendance_date);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('student_name', function($row){
                    return $row->student->full_name;
                })
                ->addColumn('roll_no', function($row){
                    return $row->student->admission_number; 
                })
                ->addColumn('class', function($row){
                    return $row->classSection->name;
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'present' => 'badge-success',
                        'absent' => 'badge-danger',
                        'late' => 'badge-warning',
                        'excused' => 'badge-info',
                        'half_day' => 'badge-primary',
                    ];
                    $class = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge '.$class.'">'.ucfirst($row->status).'</span>';
                })
                ->editColumn('attendance_date', function($row){
                    return $row->attendance_date->format('d M, Y');
                })
                ->rawColumns(['status'])
                ->make(true);
        }

        $classSectionsQuery = ClassSection::with('institution');
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) use ($institutionId) {
            $label = $item->name;
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }
            return [$item->id => $label];
        });

        return view('attendance.index', compact('classSections'));
    }

    public function create(Request $request)
    {
        $institutionId = Auth::user()->institute_id;
        
        $classSectionsQuery = ClassSection::with('institution');
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) use ($institutionId) {
            $label = $item->name;
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }
            return [$item->id => $label];
        });
        
        $students = [];
        $existingAttendance = [];
        $isUpdate = false;
        $isLocked = false;

        if ($request->filled('class_section_id') && $request->filled('date')) {
            
            $targetDate = Carbon::parse($request->date);
            
            // SRS Requirement: Editing (maximum 7 days)
            // Allow Super Admin (user_type 1) to bypass lock if needed, otherwise enforce logic
            if ($targetDate->diffInDays(now()) > 7 && !Auth::user()->hasRole('Super Admin')) {
                $isLocked = true;
            }

            $selectedClass = ClassSection::find($request->class_section_id);
            $targetInstituteId = $selectedClass ? $selectedClass->institution_id : $institutionId;

            $currentSession = AcademicSession::where('institution_id', $targetInstituteId)
                ->where('is_current', true)
                ->first();

            if (!$currentSession) {
                return back()->withErrors(['msg' => __('attendance.no_active_session')]);
            }

            $students = StudentEnrollment::with('student')
                ->where('class_section_id', $request->class_section_id)
                ->where('academic_session_id', $currentSession->id)
                ->where('status', 'active')
                ->get()
                ->pluck('student'); 

            $attendanceRecords = StudentAttendance::where('class_section_id', $request->class_section_id)
                ->where('attendance_date', $request->date)
                ->get()
                ->keyBy('student_id');

            if ($attendanceRecords->isNotEmpty()) {
                $isUpdate = true;
                $existingAttendance = $attendanceRecords;
            }
        }

        return view('attendance.create', compact('classSections', 'students', 'existingAttendance', 'isUpdate', 'isLocked'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'attendance_date' => 'required|date',
            'attendance' => 'required|array', 
            'attendance.*' => 'in:present,absent,late,excused,half_day',
        ]);

        // SRS Requirement Check: 7 Days Lock
        $targetDate = Carbon::parse($request->attendance_date);
        if ($targetDate->diffInDays(now()) > 7 && !Auth::user()->hasRole('Super Admin')) {
            return response()->json(['message' => __('attendance.attendance_locked_error')], 403);
        }

        $classSection = ClassSection::findOrFail($request->class_section_id);
        $institutionId = $classSection->institution_id;

        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if (!$currentSession) {
             return response()->json(['message' => __('attendance.no_active_session')], 422);
        }

        DB::transaction(function () use ($request, $institutionId, $currentSession) {
            foreach ($request->attendance as $studentId => $status) {
                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'attendance_date' => $request->attendance_date,
                    ],
                    [
                        'institution_id' => $institutionId,
                        'academic_session_id' => $currentSession->id,
                        'class_section_id' => $request->class_section_id,
                        'status' => $status,
                        'marked_by' => Auth::id(),
                    ]
                );
            }
        });

        return response()->json(['message' => __('attendance.messages.success_marked'), 'redirect' => route('attendance.index')]);
    }
}