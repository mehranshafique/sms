<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\Timetable;
use App\Models\ClassSection;
use App\Services\AttendanceAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AttendanceReportController extends BaseController
{
    protected $analyticsService;

    public function __construct(AttendanceAnalyticsService $analyticsService)
    {
        $this->middleware('auth');
        $this->setPageTitle(__('attendance.analytics_title'));
        $this->analyticsService = $analyticsService;
    }

    /**
     * Determines if the current institution requires Subject-Wise attendance
     */
    private function isSubjectWise($institutionId)
    {
        if (!$institutionId) return false;
        $institution = Institution::find($institutionId);
        if (!$institution) return false;
        
        $type = is_object($institution->type) ? $institution->type->value : $institution->type;
        return in_array($type, ['university', 'vocational', 'lmd']);
    }

    /**
     * Fetch Subjects for the selected class logically matching the Teacher/Admin scope
     */
    private function fetchSubjectsForClass($classId, $user)
    {
        if (!$classId) return [];
        $classSection = ClassSection::find($classId);
        if (!$classSection) return [];

        $isAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);
        $subjectIds = [];

        if ($isAdmin || ($user->staff && $classSection->staff_id == $user->staff->id)) {
            $allocated = ClassSubject::where('class_section_id', $classId)->pluck('subject_id')->toArray();
            $timetable = Timetable::where('class_section_id', $classId)->pluck('subject_id')->toArray();
            $subjectIds = array_unique(array_merge($allocated, $timetable));
            
            if (empty($subjectIds)) {
                $subjectIds = Subject::where('grade_level_id', $classSection->grade_level_id)
                    ->where('is_active', true)->pluck('id')->toArray();
            }
        } else {
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
     * Renders the List of Students for Staff and Parents.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('Student')) {
            return redirect()->route('attendance.analytics.show');
        }

        if (!$user->hasRole('Guardian') && !$user->can('student_attendance.view')) {
            abort(403, 'Unauthorized access.');
        }

        if ($request->ajax()) {
            // BUG FIX: Load the active enrollments to dynamically grab the currently assigned class
            $query = Student::with(['enrollments' => function($q) {
                $q->where('status', 'active')->latest();
            }, 'enrollments.classSection'])->select('students.*');

            if ($user->hasRole('Guardian')) {
                $parent = StudentParent::where('user_id', $user->id)->first();
                $query->where('parent_id', $parent ? $parent->id : 0);
            } else {
                $institutionId = $this->getInstitutionId();
                if ($institutionId) {
                    $query->where('institution_id', $institutionId);
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name', function($row) {
                    return $row->first_name . ' ' . $row->last_name;
                })
                ->addColumn('class', function($row) {
                    // BUG FIX: Extract class from the active enrollment relation
                    $enrollment = $row->enrollments->first();
                    return $enrollment && $enrollment->classSection ? $enrollment->classSection->name : __('attendance.not_assigned');
                })
                ->addColumn('action', function($row) {
                    $url = route('attendance.analytics.show', $row->id);
                    return '<a href="'.$url.'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('attendance.view_analytics').'"><i class="fa fa-chart-line"></i></a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('attendance.reports.index');
    }

    /**
     * Renders the Comparative Analytics Dashboard for a specific student.
     */
    public function studentReport(Request $request, $id = null)
    {
        $user = Auth::user();
        
        if ($user->hasRole('Student')) {
            $studentId = $user->student->id ?? abort(404, 'Student profile not found.');
        } elseif ($user->hasRole('Guardian')) {
            $studentId = $id;
            $parent = StudentParent::where('user_id', $user->id)->first();
            $ownsChild = Student::where('id', $studentId)->where('parent_id', $parent->id ?? 0)->exists();
            if (!$ownsChild) abort(403, 'Unauthorized access to student record.');
        } else {
            if (!$user->can('student_attendance.view')) abort(403);
            $studentId = $id;
        }

        // BUG FIX: Eager load enrollments so we can fetch the assigned class correctly
        $student = Student::with(['enrollments' => function($q) {
            $q->where('status', 'active')->latest();
        }])->findOrFail($studentId);
        
        $institutionId = $this->getInstitutionId() ?? $student->institution_id;
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access to student record.');
        }

        // Extract class section from the active enrollment
        $activeEnrollment = $student->enrollments->first();
        $classSectionId = $activeEnrollment ? $activeEnrollment->class_section_id : null;

        // Smart Subject-Wise Processing
        $isSubjectWise = $this->isSubjectWise($institutionId);
        $subjects = [];
        $selectedSubjectId = $request->get('subject_id');
        $period = $request->get('period', 'week'); 

        // Safely pass the resolved class ID to fetch subjects
        if ($isSubjectWise && $classSectionId) {
            $subjects = $this->fetchSubjectsForClass($classSectionId, $user);
        }

        if ($isSubjectWise && empty($selectedSubjectId)) {
            $stats = []; 
        } else {
            $stats = $this->analyticsService->getComparativeStats(
                $student->id, 
                $classSectionId, 
                $period, 
                $isSubjectWise, 
                $selectedSubjectId
            );
        }

        return view('attendance.reports.student_analytics', compact('student', 'stats', 'period', 'isSubjectWise', 'subjects', 'selectedSubjectId'));
    }
}