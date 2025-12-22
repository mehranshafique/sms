<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Campus;
use App\Models\AcademicSession;
use App\Models\StudentAttendance;
use App\Models\Invoice;
use App\Models\Timetable;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->setPageTitle(__('dashboard.page_title'));
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();

        // 1. Redirect based on Role
        if ($user->hasRole('Student')) {
            return $this->studentDashboard($user, $institutionId);
        }
        
        // Combine Teacher and Staff roles
        if ($user->hasRole('Teacher') || $user->hasRole('Staff') || $user->user_type == 4) {
            return $this->teacherDashboard($user, $institutionId);
        }

        // 2. Strict Check for Super Admin / Head Officer
        if ($user->hasRole(['Super Admin', 'Head Officer'])) {
            return $this->adminDashboard($institutionId);
        }

        // 3. Fallback
        return view('dashboard.dashboard');
    }

    /**
     * Admin / Super Admin View
     */
    private function adminDashboard($institutionId)
    {
        // Core Counts (Scoped)
        $studentsQuery = Student::query();
        $staffQuery = Staff::query();
        $campusesQuery = Campus::query();
        $institutesQuery = Institution::query();
        $invoiceQuery = Invoice::query();
        $attendanceQuery = StudentAttendance::query();
        $subjectQuery = Subject::query(); 

        if ($institutionId) {
            $studentsQuery->where('institution_id', $institutionId);
            $staffQuery->where('institution_id', $institutionId);
            $campusesQuery->where('institution_id', $institutionId);
            $invoiceQuery->where('institution_id', $institutionId);
            $attendanceQuery->where('institution_id', $institutionId);
            $subjectQuery->where('institution_id', $institutionId);
        }

        $totalStudents = $studentsQuery->count();
        $totalStaff = $staffQuery->count();
        $totalTeachers = $totalStaff; 
        $totalCampuses = $campusesQuery->count();
        $totalInstitutes = $institutionId ? 1 : $institutesQuery->count();
        
        // Finance Stats
        $totalFees = $invoiceQuery->sum('total_amount');
        $feesCollected = $invoiceQuery->sum('paid_amount');
        $pendingFees = $totalFees - $feesCollected;
        
        // Attendance
        $today = Carbon::today();
        $todaysAttendance = $attendanceQuery->whereDate('attendance_date', $today);
        $presentCount = (clone $todaysAttendance)->where('status', 'present')->count();
        $absentCount = (clone $todaysAttendance)->where('status', 'absent')->count();
        $lateCount = (clone $todaysAttendance)->where('status', 'late')->count();

        // Recent Students
        $recentStudents = $studentsQuery->with('institution')
            ->latest()
            ->take(5)
            ->get();

        // Current Session
        $currentSession = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first();

        // Counters
        $totalEnrollment = 0;
        $newComers = 0;
        $sessionFeesPaid = 0;
        $sessionFeesRest = 0;
        $budgetSpend = 0; 
        $budgetRest = 0;  
        $totalCourses = $subjectQuery->count();
        $totalResults = 0;
        $totalTimetables = 0;
        $totalCommunication = 0; 

        if ($currentSession) {
            $enrollmentQuery = StudentEnrollment::where('academic_session_id', $currentSession->id);
            if ($institutionId) $enrollmentQuery->where('institution_id', $institutionId);
            $totalEnrollment = $enrollmentQuery->count();

            $newComerQuery = Student::whereBetween('admission_date', [$currentSession->start_date, $currentSession->end_date]);
            if ($institutionId) $newComerQuery->where('institution_id', $institutionId);
            $newComers = $newComerQuery->count();

            $sessionInvoices = Invoice::where('academic_session_id', $currentSession->id);
            if ($institutionId) $sessionInvoices->where('institution_id', $institutionId);
            $sessionFeesPaid = $sessionInvoices->sum('paid_amount');
            $sessionFeesTotal = $sessionInvoices->sum('total_amount');
            $sessionFeesRest = $sessionFeesTotal - $sessionFeesPaid;

            $examQuery = Exam::where('academic_session_id', $currentSession->id)->where('status', 'published');
            if ($institutionId) $examQuery->where('institution_id', $institutionId);
            $totalResults = $examQuery->count();

            $timetableQuery = Timetable::where('academic_session_id', $currentSession->id);
            if ($institutionId) $timetableQuery->where('institution_id', $institutionId);
            $totalTimetables = $timetableQuery->count();
        }

        // Chart Data
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();

        $chartData = $studentsQuery->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $chartLabels = [];
        $chartValues = [];
        
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $displayDate = $date->format('M d'); 
            $record = $chartData->firstWhere('date', $formattedDate);
            $chartLabels[] = $displayDate;
            $chartValues[] = $record ? $record->count : 0;
        }

        return view('dashboard.super_admin', compact(
            'totalStudents', 'totalTeachers', 'totalStaff', 'totalCampuses', 'totalInstitutes', 
            'recentStudents', 'chartLabels', 'chartValues', 'currentSession',
            'totalFees', 'feesCollected', 'pendingFees', 'presentCount', 'absentCount', 'lateCount',
            'totalEnrollment', 'newComers', 'sessionFeesPaid', 'sessionFeesRest',
            'budgetSpend', 'budgetRest', 'totalCourses', 'totalResults', 'totalTimetables', 'totalCommunication'
        ));
    }

    /**
     * Teacher / Staff View
     */
    private function teacherDashboard($user, $institutionId)
    {
        $staff = $user->staff;
        $staffId = $staff ? $staff->id : null;

        $myStudentsCount = Student::where('institution_id', $institutionId)->count(); 
        
        $today = strtolower(now()->format('l'));
        $todayClasses = collect();
        $myCoursesCount = 0;
        $myTotalClasses = 0;

        if ($staffId) {
            $todayClasses = Timetable::with(['classSection', 'subject'])
                ->where('teacher_id', $staffId)
                ->where('day_of_week', $today)
                ->orderBy('start_time')
                ->get();
                
            $myCoursesCount = Timetable::where('teacher_id', $staffId)
                ->distinct('subject_id')
                ->count('subject_id');
                
            $myTotalClasses = Timetable::where('teacher_id', $staffId)->count();
        }
            
        $currentSession = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first();

        return view('dashboard.teacher', compact(
            'myStudentsCount', 'todayClasses', 'currentSession', 'myCoursesCount', 'myTotalClasses'
        ));
    }

    /**
     * Student View
     */
    private function studentDashboard($user, $institutionId)
    {
        $student = $user->student;
        
        // Fix: Return error view, but variables will be undefined if handled in view logic
        if (!$student) {
            return view('dashboard.student', ['error' => 'No Student Profile Found']);
        }

        // Attendance Stats
        $totalDays = StudentAttendance::where('student_id', $student->id)->count();
        $presentDays = StudentAttendance::where('student_id', $student->id)->where('status', 'present')->count();
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;

        // Fees (FIXED: Added paidFees calculation)
        $invoices = Invoice::where('student_id', $student->id);
        $totalFees = (clone $invoices)->sum('total_amount');
        $paidFees = (clone $invoices)->sum('paid_amount'); 
        $unpaidInvoices = (clone $invoices)->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->sum(DB::raw('total_amount - paid_amount'));

        // Today's Timetable
        $currentClassId = $student->enrollments()->where('status', 'active')->value('class_section_id');
        $today = strtolower(now()->format('l'));
        $todayClasses = collect();

        if ($currentClassId) {
            $todayClasses = Timetable::with(['subject', 'teacher.user'])
                ->where('class_section_id', $currentClassId)
                ->where('day_of_week', $today)
                ->orderBy('start_time')
                ->get();
        }
        
        // Results
        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        $resultsCount = 0;
        if($currentSession) {
             $resultsCount = \App\Models\ExamRecord::where('student_id', $student->id)
                ->whereHas('exam', function($q) use ($currentSession) {
                    $q->where('academic_session_id', $currentSession->id)->where('status', 'published');
                })->distinct('exam_id')->count();
        }

        return view('dashboard.student', compact(
            'student', 'attendancePercentage', 'unpaidInvoices', 'todayClasses', 
            'totalFees', 'paidFees', 'resultsCount', 'currentSession'
        ));
    }
}