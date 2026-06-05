<?php

namespace App\Http\Controllers;

use App\Models\StudentAttendance;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use App\Models\InstitutionSetting;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\Timetable;
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
        $this->middleware(PermissionMiddleware::class . ':student_attendance.view')->only(['index', 'report', 'printReport']);
        $this->middleware(PermissionMiddleware::class . ':student_attendance.create')->only(['create', 'store']);
        
        $this->setPageTitle(__('attendance.page_title'));
    }

    /**
     * Determines if the current institution requires Subject-Wise attendance
     */
    private function isSubjectWise($institutionId)
    {
        if (!$institutionId) return false;
        $institution = Institution::find($institutionId);
        if (!$institution) return false;
        
        // Safely extract the type whether it's cast to an Enum object or stored as a raw string
        $type = is_object($institution->type) ? $institution->type->value : $institution->type;
        return in_array($type, ['university', 'vocational', 'lmd']);
    }

    /**
     * Fetch Subjects for the logged-in teacher and selected class
     */
    private function fetchSubjectsForClass($classId, $user)
    {
        if (!$classId) return [];
        $classSection = ClassSection::find($classId);
        if (!$classSection) return [];

        $isAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);
        $subjectIds = [];

        if ($isAdmin || ($user->staff && $classSection->staff_id == $user->staff->id)) {
            // Admin or Homeroom Teacher: gets all allocated subjects for the class
            $allocated = ClassSubject::where('class_section_id', $classId)->pluck('subject_id')->toArray();
            $timetable = Timetable::where('class_section_id', $classId)->pluck('subject_id')->toArray();
            $subjectIds = array_unique(array_merge($allocated, $timetable));
            
            if (empty($subjectIds)) {
                $subjectIds = Subject::where('grade_level_id', $classSection->grade_level_id)
                    ->where('is_active', true)->pluck('id')->toArray();
            }
        } else {
            // Subject Teacher specific filtering
            if ($user->staff) {
                $staffId = $user->staff->id;
                $allocated = ClassSubject::where('class_section_id', $classId)->where('teacher_id', $staffId)->pluck('subject_id')->toArray();
                $timetable = Timetable::where('class_section_id', $classId)->where('teacher_id', $staffId)->pluck('subject_id')->toArray();
                $subjectIds = array_unique(array_merge($allocated, $timetable));
            }
        }

        return Subject::with('academicUnit')
            ->whereIn('id', $subjectIds)
            ->get()
            ->mapWithKeys(function($sub) {
                $name = $sub->name . ($sub->academicUnit ? ' (' . $sub->academicUnit->code . ')' : '');
                return [$sub->id => $name];
            })->toArray();
    }

    /**
     * AJAX Endpoint for Create View
     */
    public function getSubjects(Request $request)
    {
        $subjects = $this->fetchSubjectsForClass($request->class_section_id, Auth::user());
        return response()->json($subjects);
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id ?? session('active_institution_id');
        $isSubjectWise = $this->isSubjectWise($institutionId);

        if ($request->ajax()) {
            $data = StudentAttendance::with(['student', 'classSection.gradeLevel', 'subject'])
                ->select('student_attendances.*')
                ->latest('student_attendances.created_at');

            if ($institutionId) {
                $data->where('student_attendances.institution_id', $institutionId);
            }

            if ($request->filled('class_section_id')) {
                $data->where('student_attendances.class_section_id', $request->class_section_id);
            }
            if ($request->filled('attendance_date')) {
                $data->whereDate('student_attendances.attendance_date', $request->attendance_date);
            }
            if ($isSubjectWise && $request->filled('subject_id')) {
                $data->where('student_attendances.subject_id', $request->subject_id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('student_name', function($row){
                    return $row->student->full_name ?? 'Unknown';
                })
                ->addColumn('roll_no', function($row){
                    return $row->student->admission_number ?? '-'; 
                })
                ->addColumn('class', function($row){
                    $grade = $row->classSection->gradeLevel->name ?? '';
                    return ($grade ? $grade . ' ' : '') . $row->classSection->name;
                })
                ->addColumn('subject', function($row) use ($isSubjectWise) {
                    if (!$isSubjectWise) return '-';
                    return $row->subject ? $row->subject->name : __('attendance.not_assigned');
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

        $user = Auth::user();
        $isAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);

        $classSectionsQuery = ClassSection::with(['institution', 'gradeLevel']);
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        
        // Strict Teacher Scope Filtering
        if (!$isAdmin && $user->hasRole('Teacher')) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                $classSectionsQuery->where(function($q) use ($staffId) {
                    $q->where('staff_id', $staffId) // Homeroom
                      ->orWhereHas('timetables', function($t) use ($staffId) { $t->where('teacher_id', $staffId); })
                      ->orWhereHas('classSubjects', function($c) use ($staffId) { $c->where('teacher_id', $staffId); });
                });
            } else {
                $classSectionsQuery->whereRaw('1 = 0');
            }
        }
        
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) use ($institutionId) {
            $grade = $item->gradeLevel->name ?? '';
            $label = ($grade ? $grade . ' ' : '') . $item->name;
            if (!$institutionId && $item->institution) $label .= ' (' . $item->institution->code . ')';
            return [$item->id => $label];
        });

        $subjects = [];
        if ($isSubjectWise && $request->filled('class_section_id')) {
            $subjects = $this->fetchSubjectsForClass($request->class_section_id, Auth::user());
        }

        return view('attendance.index', compact('classSections', 'isSubjectWise', 'subjects'));
    }

    public function report(Request $request)
    {
        $data = $this->getReportData($request);
        return view('attendance.report', $data);
    }

    public function printReport(Request $request)
    {
        $data = $this->getReportData($request);
        return view('attendance.print', $data);
    }

    private function getReportData(Request $request)
    {
        $user = Auth::user();
        $institutionId = $user->institute_id ?? session('active_institution_id');
        $isSubjectWise = $this->isSubjectWise($institutionId);
        $isAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);

        $classSectionsQuery = ClassSection::with(['institution', 'gradeLevel']);
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        
        if (!$isAdmin && $user->hasRole('Teacher') && $user->staff) {
            $staffId = $user->staff->id;
            $classSectionsQuery->where(function($q) use ($staffId) {
                $q->where('staff_id', $staffId)
                  ->orWhereHas('timetables', function($t) use ($staffId) { $t->where('teacher_id', $staffId); })
                  ->orWhereHas('classSubjects', function($c) use ($staffId) { $c->where('teacher_id', $staffId); });
            });
        }
        
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) {
             $grade = $item->gradeLevel->name ?? '';
             return [$item->id => ($grade ? $grade . ' ' : '') . $item->name];
        });

        $students = [];
        $attendanceMap = [];
        $daysInMonth = 0;
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;
        $selectedClass = null;
        $selectedSubject = null;
        $subjects = [];

        if ($isSubjectWise && $request->filled('class_section_id')) {
            $subjects = $this->fetchSubjectsForClass($request->class_section_id, $user);
        }

        if ($request->filled('class_section_id')) {
            
            if ($isSubjectWise && !$request->filled('subject_id')) {
                // If subject-wise, force them to select a subject before loading the grid
                session()->flash('warning', __('attendance.select_subject_for_report'));
            } else {
                $selectedClass = ClassSection::with('gradeLevel')->find($request->class_section_id);
                
                if ($isSubjectWise && $request->filled('subject_id')) {
                    $selectedSubject = Subject::find($request->subject_id);
                }
                
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();
                $daysInMonth = $startDate->daysInMonth;

                $students = StudentEnrollment::with('student')
                    ->where('class_section_id', $request->class_section_id)
                    ->where('status', 'active')
                    ->get();

                $recordsQuery = StudentAttendance::where('class_section_id', $request->class_section_id)
                    ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

                if ($isSubjectWise) {
                    $recordsQuery->where('subject_id', $request->subject_id);
                } else {
                    $recordsQuery->whereNull('subject_id');
                }

                $records = $recordsQuery->get();

                foreach ($records as $record) {
                    $day = (int) $record->attendance_date->format('d');
                    $attendanceMap[$record->student_id][$day] = $record->status;
                }
            }
        }

        return compact('classSections', 'students', 'attendanceMap', 'daysInMonth', 'year', 'month', 'selectedClass', 'isSubjectWise', 'subjects', 'selectedSubject');
    }

    public function create(Request $request) 
    { 
        $user = Auth::user();
        $institutionId = $user->institute_id ?? session('active_institution_id');
        $isSubjectWise = $this->isSubjectWise($institutionId);
        $isAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);
        
        $classSectionsQuery = ClassSection::with(['institution', 'gradeLevel']);
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        
        if (!$isAdmin && $user->hasRole('Teacher') && $user->staff) {
            $staffId = $user->staff->id;
            $classSectionsQuery->where(function($q) use ($staffId) {
                $q->where('staff_id', $staffId)
                  ->orWhereHas('timetables', function($t) use ($staffId) { $t->where('teacher_id', $staffId); })
                  ->orWhereHas('classSubjects', function($c) use ($staffId) { $c->where('teacher_id', $staffId); });
            });
        }
        
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) use ($institutionId) {
            $grade = $item->gradeLevel->name ?? '';
            $label = ($grade ? $grade . ' ' : '') . $item->name;
            if (!$institutionId && $item->institution) $label .= ' (' . $item->institution->code . ')';
            return [$item->id => $label];
        });
        
        $students = [];
        $existingAttendance = [];
        $isUpdate = false;
        $isLocked = false;
        $lockReason = '';
        $subjects = [];

        if ($isSubjectWise && $request->filled('class_section_id')) {
            $subjects = $this->fetchSubjectsForClass($request->class_section_id, $user);
        }

        if ($request->filled('class_section_id') && $request->filled('date')) {
            
            if ($isSubjectWise && !$request->filled('subject_id')) {
                return back()->withErrors(['msg' => __('attendance.subject_required')]);
            }

            $targetDate = Carbon::parse($request->date);
            $selectedClass = ClassSection::find($request->class_section_id);
            $targetInstituteId = $selectedClass ? $selectedClass->institution_id : $institutionId;

            if (!$isAdmin) {
                $isBlocked = InstitutionSetting::get($targetInstituteId, 'attendance_locked', 0);
                if ($isBlocked) {
                    $isLocked = true;
                    $lockReason = __('attendance.admin_blocked');
                } else {
                    $graceDays = InstitutionSetting::get($targetInstituteId, 'attendance_grace_period', 7);
                    if ($targetDate->lt(now()->subDays($graceDays)->startOfDay())) {
                        $isLocked = true;
                        $lockReason = __('attendance.grace_period_exceeded', ['days' => $graceDays]);
                    }
                }
            }

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

            $query = StudentAttendance::where('class_section_id', $request->class_section_id)
                ->where('attendance_date', $request->date);

            if ($isSubjectWise) {
                $query->where('subject_id', $request->subject_id);
            } else {
                $query->whereNull('subject_id');
            }

            $attendanceRecords = $query->get()->keyBy('student_id');

            if ($attendanceRecords->isNotEmpty()) {
                $isUpdate = true;
                $existingAttendance = $attendanceRecords;
            }
        }

        return view('attendance.create', compact('classSections', 'students', 'existingAttendance', 'isUpdate', 'isLocked', 'lockReason', 'isSubjectWise', 'subjects'));
    }

    public function store(Request $request) {
        $institutionId = Auth::user()->institute_id ?? session('active_institution_id');
        $isSubjectWise = $this->isSubjectWise($institutionId);

        $rules = [
            'class_section_id' => 'required|exists:class_sections,id',
            'attendance_date' => 'required|date',
            'attendance' => 'required|array', 
            'attendance.*' => 'in:present,absent,late,excused,half_day',
        ];

        if ($isSubjectWise) {
            $rules['subject_id'] = 'required|exists:subjects,id';
        }

        $request->validate($rules);

        $classSection = ClassSection::findOrFail($request->class_section_id);
        $targetInstituteId = $classSection->institution_id;

        if (!Auth::user()->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            $isBlocked = InstitutionSetting::get($targetInstituteId, 'attendance_locked', 0);
            if ($isBlocked) {
                return response()->json(['message' => __('attendance.admin_blocked_error')], 403);
            }

            $targetDate = Carbon::parse($request->attendance_date);
            $graceDays = InstitutionSetting::get($targetInstituteId, 'attendance_grace_period', 7);
            
            if ($targetDate->lt(now()->subDays($graceDays)->startOfDay())) {
                return response()->json(['message' => __('attendance.grace_period_error', ['days' => $graceDays])], 403);
            }
        }

        $currentSession = AcademicSession::where('institution_id', $targetInstituteId)->where('is_current', true)->first();

        if (!$currentSession) {
             return response()->json(['message' => __('attendance.no_active_session')], 422);
        }

        DB::transaction(function () use ($request, $targetInstituteId, $currentSession, $isSubjectWise) {
            foreach ($request->attendance as $studentId => $status) {
                
                $matchAttributes = [
                    'student_id' => $studentId,
                    'attendance_date' => $request->attendance_date,
                    'class_section_id' => $request->class_section_id,
                ];

                if ($isSubjectWise) {
                    $matchAttributes['subject_id'] = $request->subject_id;
                } else {
                    $matchAttributes['subject_id'] = null; // Explicitly ensure it is null
                }

                StudentAttendance::updateOrCreate(
                    $matchAttributes,
                    [
                        'institution_id' => $targetInstituteId,
                        'academic_session_id' => $currentSession->id,
                        'status' => $status,
                        'marked_by' => Auth::id(),
                    ]
                );
            }
        });

        return response()->json(['message' => __('attendance.messages.success_marked'), 'redirect' => route('attendance.index')]);
    }
}