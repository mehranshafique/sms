<?php

namespace App\Http\Controllers;

use App\Models\StudentAttendance;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use App\Models\InstitutionSetting;
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
        $this->middleware(PermissionMiddleware::class . ':student_attendance.view')->only(['index', 'report']);
        $this->middleware(PermissionMiddleware::class . ':student_attendance.create')->only(['create', 'store']);
        
        $this->setPageTitle(__('attendance.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        if ($request->ajax()) {
            $data = StudentAttendance::with(['student', 'classSection.gradeLevel'])
                ->select('student_attendances.*')
                ->latest('student_attendances.created_at');

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
                    return $row->student->full_name ?? 'Unknown';
                })
                // UPDATED: Explicitly use admission_number instead of roll_number or id
                ->addColumn('roll_no', function($row){
                    return $row->student->admission_number ?? '-'; 
                })
                ->addColumn('class', function($row){
                    $grade = $row->classSection->gradeLevel->name ?? '';
                    return $row->classSection->name . ($grade ? ' (' . $grade . ')' : '');
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

        $classSectionsQuery = ClassSection::with(['institution', 'gradeLevel']);
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) use ($institutionId) {
            $grade = $item->gradeLevel->name ?? '';
            $label = $item->name . ($grade ? ' (' . $grade . ')' : '');
            
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }
            return [$item->id => $label];
        });

        return view('attendance.index', compact('classSections'));
    }

    /**
     * Report: Grid View / Register View
     */
    public function report(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        $classSectionsQuery = ClassSection::with(['institution', 'gradeLevel']);
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) {
             $grade = $item->gradeLevel->name ?? '';
             return [$item->id => $item->name . ($grade ? ' (' . $grade . ')' : '')];
        });

        $students = [];
        $attendanceMap = [];
        $daysInMonth = 0;
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;

        if ($request->filled('class_section_id')) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            $daysInMonth = $startDate->daysInMonth;

            // Fetch Students in Class
            $students = StudentEnrollment::with('student')
                ->where('class_section_id', $request->class_section_id)
                ->where('status', 'active')
                ->get();

            // Fetch Attendance Records for the month
            $records = StudentAttendance::where('class_section_id', $request->class_section_id)
                ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            // Map: [student_id][day_number] = status
            foreach ($records as $record) {
                $day = (int) $record->attendance_date->format('d');
                $attendanceMap[$record->student_id][$day] = $record->status;
            }
        }

        return view('attendance.report', compact('classSections', 'students', 'attendanceMap', 'daysInMonth', 'year', 'month'));
    }

    public function create(Request $request)
    {
        $institutionId = Auth::user()->institute_id;
        
        $classSectionsQuery = ClassSection::with(['institution', 'gradeLevel']);
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) use ($institutionId) {
            $grade = $item->gradeLevel->name ?? '';
            $label = $item->name . ($grade ? ' (' . $grade . ')' : '');
            
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }
            return [$item->id => $label];
        });
        
        $students = [];
        $existingAttendance = [];
        $isUpdate = false;
        $isLocked = false;
        $lockReason = '';

        if ($request->filled('class_section_id') && $request->filled('date')) {
            
            $targetDate = Carbon::parse($request->date);
            $selectedClass = ClassSection::find($request->class_section_id);
            $targetInstituteId = $selectedClass ? $selectedClass->institution_id : $institutionId;

            // --- SETTINGS CHECK ---
            if (!Auth::user()->hasRole('Super Admin')) {
                // 1. Check Global Block
                $isBlocked = InstitutionSetting::get($targetInstituteId, 'attendance_locked', 0);
                
                if ($isBlocked) {
                    $isLocked = true;
                    $lockReason = __('attendance.admin_blocked');
                } else {
                    // 2. Check Grace Period
                    $graceDays = InstitutionSetting::get($targetInstituteId, 'attendance_grace_period', 7);
                    if ($targetDate->lt(now()->subDays($graceDays)->startOfDay())) {
                        $isLocked = true;
                        $lockReason = __('attendance.grace_period_exceeded', ['days' => $graceDays]);
                    }
                }
            }
            // ---------------------

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

        return view('attendance.create', compact('classSections', 'students', 'existingAttendance', 'isUpdate', 'isLocked', 'lockReason'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'attendance_date' => 'required|date',
            'attendance' => 'required|array', 
            'attendance.*' => 'in:present,absent,late,excused,half_day',
        ]);

        $classSection = ClassSection::findOrFail($request->class_section_id);
        $institutionId = $classSection->institution_id;

        // --- SETTINGS ENFORCEMENT ---
        if (!Auth::user()->hasRole('Super Admin')) {
            $isBlocked = InstitutionSetting::get($institutionId, 'attendance_locked', 0);
            if ($isBlocked) {
                return response()->json(['message' => __('attendance.admin_blocked_error')], 403);
            }

            $targetDate = Carbon::parse($request->attendance_date);
            $graceDays = InstitutionSetting::get($institutionId, 'attendance_grace_period', 7);
            
            if ($targetDate->lt(now()->subDays($graceDays)->startOfDay())) {
                return response()->json(['message' => __('attendance.grace_period_error', ['days' => $graceDays])], 403);
            }
        }

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