<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Staff;
use App\Models\User;
use App\Models\Campus;
use App\Models\AcademicSession;
use App\Models\StudentAttendance;
use App\Models\Invoice; // School Invoices
use App\Models\PlatformInvoice; // Super Admin Billing
use App\Models\Subscription;
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

        // 1. MAIN ADMIN (PLATFORM OWNER) CHECK
        if ($user->hasRole('Super Admin')) {
            // FIX: If Super Admin has switched context to a specific school, show that school's dashboard
            // if ($institutionId != '0') {
            //     return $this->schoolAdminDashboard($institutionId);
            // }
            // Otherwise, show the Global Platform Dashboard
            return $this->platformAdminDashboard();
        }

        // 2. School Admin (Head Officer)
        if ($user->hasRole('Head Officer')) {
            return $this->schoolAdminDashboard($institutionId);
        }

        // 3. Teacher/Staff
        if ($user->hasRole(['Teacher', 'Staff']) || $user->user_type == 4) {
            return $this->teacherDashboard($user, $institutionId);
        }

        // 4. Student
        if ($user->hasRole('Student')) {
            return $this->studentDashboard($user, $institutionId);
        }

        return view('dashboard.dashboard');
    }

    /**
     * MAIN ADMIN DASHBOARD (Platform Level)
     * Calculates real platform-wide statistics.
     */
    private function platformAdminDashboard()
    {
        // --- 1. Reporting & Stats ---
        $totalInstitutions = Institution::count();
        $totalStudents = Student::count(); // Global Count
        $totalStaff = Staff::count(); 
        
        $newInstitutionsCount = Institution::where('created_at', '>=', now()->subDays(30))->count();
        $activeInstitutionsCount = Institution::where('is_active', true)->count();

        // --- 2. Finance / Billing ---
        $pendingFunds = PlatformInvoice::where('status', 'unpaid')->orWhere('status', 'overdue')->sum('total_amount');
        $validatedFunds = PlatformInvoice::where('status', 'paid')->sum('total_amount');

        // --- 3. Statistics (Student by Year) ---
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $chartData = Student::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $chartLabels = [];
        $chartValues = [];
        $period = \Carbon\CarbonPeriod::create($startDate, '1 month', $endDate);
        
        foreach ($period as $dt) {
            $key = $dt->format('Y-m');
            $record = $chartData->firstWhere('date', $key);
            $chartLabels[] = $dt->format('M Y');
            $chartValues[] = $record ? $record->count : 0;
        }

        $recentInstitutions = Institution::latest()->take(5)->get();

        $expiredInstitutions = Subscription::where('end_date', '<', now())
            ->where('status', 'active')
            ->distinct('institution_id')
            ->count('institution_id');

        $auditLogCount = \App\Models\AuditLog::where('created_at', '>=', now()->subDay())->count();

        return view('dashboard.main_admin', compact(
            'totalInstitutions', 'newInstitutionsCount', 'activeInstitutionsCount',
            'totalStudents', 'totalStaff', 'pendingFunds', 'validatedFunds',
            'chartLabels', 'chartValues', 'recentInstitutions',
            'expiredInstitutions', 'auditLogCount'
        ));
    }

    /**
     * School Admin View (Head Officer)
     */
    private function schoolAdminDashboard($institutionId)
    {
        // dd($institutionId);
        // Core Counts (Scoped to Institution)
        $studentsQuery = Student::where('institution_id', $institutionId);
        $staffQuery = Staff::where('institution_id', $institutionId);
        $invoiceQuery = Invoice::where('institution_id', $institutionId);
        
        $totalStudents = $studentsQuery->count();
        $totalStaff = $staffQuery->count();
        $totalTeachers = $staffQuery->whereNotNull('designation')->count(); // Adjust filter if needed
        $totalCampuses = Campus::where('institution_id', $institutionId)->count();
        $totalInstitutes = 1;

        // Finance Stats (School Level)
        $totalFees = $invoiceQuery->sum('total_amount');
        $feesCollected = $invoiceQuery->sum('paid_amount');
        $pendingFees = $totalFees - $feesCollected;

        // Recent Students
        $recentStudents = $studentsQuery->with('institution')->latest()->take(5)->get();

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
        $totalCourses = Subject::where('institution_id', $institutionId)->count();
        $totalResults = 0;
        $totalTimetables = 0;
        $totalCommunication = 0; 

        if ($currentSession) {
            $totalEnrollment = StudentEnrollment::where('academic_session_id', $currentSession->id)
                ->where('institution_id', $institutionId)->count();

            $newComers = Student::where('institution_id', $institutionId)
                ->whereBetween('admission_date', [$currentSession->start_date, $currentSession->end_date])
                ->count();

            $sessionInvoices = Invoice::where('academic_session_id', $currentSession->id)
                ->where('institution_id', $institutionId);
            $sessionFeesPaid = $sessionInvoices->sum('paid_amount');
            $sessionFeesTotal = $sessionInvoices->sum('total_amount');
            $sessionFeesRest = $sessionFeesTotal - $sessionFeesPaid;

            $totalResults = Exam::where('academic_session_id', $currentSession->id)
                ->where('institution_id', $institutionId)
                ->where('status', 'published')->count();

            $totalTimetables = Timetable::where('academic_session_id', $currentSession->id)
                ->where('institution_id', $institutionId)->count();
        }

        // Attendance Snippet
        $today = Carbon::today();
        $todaysAttendance = StudentAttendance::where('institution_id', $institutionId)
            ->whereDate('attendance_date', $today);
        $presentCount = (clone $todaysAttendance)->where('status', 'present')->count();
        $absentCount = (clone $todaysAttendance)->where('status', 'absent')->count();
        $lateCount = (clone $todaysAttendance)->where('status', 'late')->count();

        // Chart Data (School Specific)
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();
        
        $chartQuery = Student::where('institution_id', $institutionId) // Explicit Scope
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $chartLabels = [];
        $chartValues = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $displayDate = $date->format('M d'); 
            $record = $chartQuery->firstWhere('date', $formattedDate);
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
            $myCoursesCount = Timetable::where('teacher_id', $staffId)->distinct('subject_id')->count('subject_id');
            $myTotalClasses = Timetable::where('teacher_id', $staffId)->count();
        }
        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        return view('dashboard.teacher', compact('myStudentsCount', 'todayClasses', 'currentSession', 'myCoursesCount', 'myTotalClasses'));
    }

    private function studentDashboard($user, $institutionId)
    {
        $student = $user->student;
        if (!$student) return view('dashboard.student', ['error' => 'No Student Profile Found']);

        $totalDays = StudentAttendance::where('student_id', $student->id)->count();
        $presentDays = StudentAttendance::where('student_id', $student->id)->where('status', 'present')->count();
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;

        $invoices = Invoice::where('student_id', $student->id);
        $totalFees = (clone $invoices)->sum('total_amount');
        $paidFees = (clone $invoices)->sum('paid_amount'); 
        $unpaidInvoices = (clone $invoices)->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->sum(DB::raw('total_amount - paid_amount'));

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
        
        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        $resultsCount = 0;
        if($currentSession) {
             $resultsCount = \App\Models\ExamRecord::where('student_id', $student->id)
                ->whereHas('exam', function($q) use ($currentSession) {
                    $q->where('academic_session_id', $currentSession->id)->where('status', 'published');
                })->distinct('exam_id')->count();
        }

        return view('dashboard.student', compact('student', 'attendancePercentage', 'unpaidInvoices', 'todayClasses', 'totalFees', 'paidFees', 'resultsCount', 'currentSession'));
    }
}