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
use App\Models\Payment; // Added Payment Model
use App\Models\FeeStructure; // Added FeeStructure
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
        
        // Get Active Context from Session
        $activeInstId = session('active_institution_id');

        // 1. MAIN ADMIN (PLATFORM OWNER) CHECK
        if ($user->hasRole('Super Admin') || $user->hasRole('School Admin')) {
            // If Super Admin switched to a specific school
            if ($activeInstId && $activeInstId !== 'global') {
                return $this->schoolAdminDashboard($activeInstId);
            }
            // Otherwise, show the Global Platform Dashboard
            return $this->platformAdminDashboard();
        }

        // 2. School Admin (Head Officer)
        if ($user->hasRole('Head Officer')) {
            $myInstitutes = $user->institutes; // Assuming relationship exists
            
            // Check if Head Officer has multiple institutions AND is in Global Mode
            if ($myInstitutes->count() > 1 && ($activeInstId === 'global' || !$activeInstId)) {
                return $this->multiSchoolDashboard($user, $myInstitutes);
            }

            // Otherwise, determine the specific school ID
            $institutionId = ($activeInstId && $activeInstId !== 'global') 
                ? $activeInstId 
                : ($myInstitutes->first()->id ?? $user->institute_id);

            return $this->schoolAdminDashboard($institutionId);
        }

        // 3. Teacher/Staff
        if ($user->hasRole(['Teacher', 'Staff']) || $user->user_type == 4) {
            $institutionId = $this->getInstitutionId(); // Fallback to helper
            return $this->teacherDashboard($user, $institutionId);
        }

        // 4. Student
        if ($user->hasRole('Student')) {
            $institutionId = $this->getInstitutionId(); // Fallback to helper
            return $this->studentDashboard($user, $institutionId);
        }

        return view('dashboard.dashboard');
    }

    /**
     * NEW: Multi-School Dashboard for Head Officers
     * Aggregates stats across all assigned schools.
     */
    private function multiSchoolDashboard($user, $institutes)
    {
        $instituteIds = $institutes->pluck('id');

        // 1. Aggregated Counts
        $totalSchools = $institutes->count();
        $activeSchools = $institutes->where('is_active', true)->count();
        
        $totalStudents = Student::whereIn('institution_id', $instituteIds)->count();
        $totalStaff = Staff::whereIn('institution_id', $instituteIds)->count();

        // 2. Aggregated Finances (School Invoices)
        $invoiceQuery = Invoice::whereIn('institution_id', $instituteIds);
        $totalRevenue = $invoiceQuery->sum('total_amount');
        $collectedRevenue = $invoiceQuery->sum('paid_amount');
        $pendingRevenue = $totalRevenue - $collectedRevenue;

        // 3. School List with Mini-Stats
        // We attach student count to each institute object for the table
        $institutes->map(function($inst) {
            $inst->student_count = Student::where('institution_id', $inst->id)->count();
            $inst->staff_count = Staff::where('institution_id', $inst->id)->count();
            return $inst;
        });

        // 4. Recent Activity (Audit Logs filtered by these schools)
        // Assuming AuditLog has 'institution_id'
        $auditLogCount = \App\Models\AuditLog::whereIn('institution_id', $instituteIds)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return view('dashboard.head_officer_global', compact(
            'totalSchools', 'activeSchools', 'totalStudents', 'totalStaff',
            'totalRevenue', 'collectedRevenue', 'pendingRevenue',
            'institutes', 'auditLogCount'
        ));
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
     * School Admin View (Head Officer) - OVERHAULED FINANCIALS
     */
    private function schoolAdminDashboard($institutionId)
    {
        // Core Counts (Scoped to Institution)
        $studentsQuery = Student::where('institution_id', $institutionId);
        $staffQuery = Staff::where('institution_id', $institutionId);
        
        $totalStudents = $studentsQuery->count();
        $totalStaff = $staffQuery->count();
        $totalTeachers = $staffQuery->whereNotNull('designation')->count(); 
        $totalCampuses = Campus::where('institution_id', $institutionId)->count();
        $totalInstitutes = 1;

        // Current Session
        $currentSession = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first();

        // --- ENROLLMENT-BASED FINANCIAL LOGIC ---
        $expectedTotal = 0;
        $collectedTotal = 0;
        $remainingToCollect = 0;
        $paidCount = 0;
        $unpaidCount = 0;
        $installmentStats = [];

        $totalEnrollment = 0;
        $newComers = 0;
        
        // Other Stats
        $budgetSpend = 0; 
        $budgetRest = 0;  
        $totalCourses = Subject::where('institution_id', $institutionId)->count();
        $totalResults = 0;
        $totalTimetables = 0;
        $totalCommunication = 0; 

        if ($currentSession) {
            // 1. Enrollment Counts
            $enrollments = StudentEnrollment::where('academic_session_id', $currentSession->id)
                ->where('institution_id', $institutionId)
                ->where('status', 'active')
                ->get();
            
            $totalEnrollment = $enrollments->count();

            $newComers = Student::where('institution_id', $institutionId)
                ->whereBetween('admission_date', [$currentSession->start_date, $currentSession->end_date])
                ->count();

            // 2. EXPECTED REVENUE CALCULATION
            // Formula: Sum of (Student Grade's Fee Amount) for all active students
            // We need to fetch fee structures for the current session
            
            $feeStructures = FeeStructure::where('institution_id', $institutionId)
                ->where('academic_session_id', $currentSession->id)
                ->get();

            // Calculate Expected Revenue per Student
            // Optimized: Group students by Grade Level
            $studentsByGrade = $enrollments->groupBy('grade_level_id');

            foreach ($studentsByGrade as $gradeId => $gradeEnrollments) {
                // Get fees applicable to this grade
                $gradeFeeTotal = 0;
                
                // Check if a GLOBAL fee structure exists for this grade
                $globalFee = $feeStructures->where('grade_level_id', $gradeId)
                    ->where('payment_mode', 'global')
                    ->first();

                if ($globalFee) {
                    $gradeFeeTotal = $globalFee->amount;
                } else {
                    // Sum installments if no global fee defined
                    $gradeFeeTotal = $feeStructures->where('grade_level_id', $gradeId)
                        ->where('payment_mode', 'installment')
                        ->sum('amount');
                }

                $expectedTotal += ($gradeFeeTotal * $gradeEnrollments->count());
            }

            // 3. COLLECTED REVENUE (Real Payments)
            // Sum payments linked to invoices for this session
            $collectedTotal = Payment::where('institution_id', $institutionId)
                ->whereHas('invoice', function($q) use ($currentSession) {
                    $q->where('academic_session_id', $currentSession->id);
                })
                ->sum('amount');

            $remainingToCollect = $expectedTotal - $collectedTotal;

            // 4. PAID vs UNPAID STUDENTS COUNT
            // A student is "Paid" if their total payments >= their grade's expected fee
            
            // Get total paid per student for this session
            // FIX: Qualified column names to prevent ambiguity error
            $studentPayments = Payment::where('payments.institution_id', $institutionId)
                ->whereHas('invoice', function($q) use ($currentSession) {
                    $q->where('academic_session_id', $currentSession->id);
                })
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->selectRaw('invoices.student_id, SUM(payments.amount) as total_paid')
                ->groupBy('invoices.student_id')
                ->pluck('total_paid', 'invoices.student_id');

            foreach ($enrollments as $enrollment) {
                $studentId = $enrollment->student_id;
                $gradeId = $enrollment->grade_level_id;
                
                // Determine expected fee for this student
                $globalFee = $feeStructures->where('grade_level_id', $gradeId)->where('payment_mode', 'global')->first();
                $expected = $globalFee ? $globalFee->amount : $feeStructures->where('grade_level_id', $gradeId)->where('payment_mode', 'installment')->sum('amount');
                
                // Get actual paid
                $paid = $studentPayments[$studentId] ?? 0;

                // Threshold: 99% paid counts as Paid (to handle rounding errors)
                if ($expected > 0 && $paid >= ($expected * 0.99)) {
                    $paidCount++;
                } else {
                    $unpaidCount++;
                }
            }

            // 5. INSTALLMENT BREAKDOWN
            // Calculate progress for TR1, TR2, etc.
            $installmentFees = $feeStructures->where('payment_mode', 'installment')
                ->groupBy('installment_order')
                ->sortKeys();

            foreach ($installmentFees as $order => $fees) {
                $instExpected = 0;
                
                foreach ($fees as $fee) {
                    // How many students in this grade?
                    $count = isset($studentsByGrade[$fee->grade_level_id]) ? $studentsByGrade[$fee->grade_level_id]->count() : 0;
                    $instExpected += ($fee->amount * $count);
                }
                
                $installmentStats[] = [
                    'label' => $fees->first()->name ?? "Installment $order",
                    'expected' => $instExpected,
                    'order' => $order
                ];
            }

            $totalResults = Exam::where('academic_session_id', $currentSession->id)
                ->where('institution_id', $institutionId)
                ->where('status', 'published')->count();

            $totalTimetables = Timetable::where('academic_session_id', $currentSession->id)
                ->where('institution_id', $institutionId)->count();
        }

        // Attendance Snippet (Same as before)
        $today = Carbon::today();
        $todaysAttendance = StudentAttendance::where('institution_id', $institutionId)
            ->whereDate('attendance_date', $today);
        $presentCount = (clone $todaysAttendance)->where('status', 'present')->count();
        $absentCount = (clone $todaysAttendance)->where('status', 'absent')->count();
        $lateCount = (clone $todaysAttendance)->where('status', 'late')->count();

        // Chart Data (Same as before)
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();
        
        $chartQuery = Student::where('institution_id', $institutionId) 
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

        // Pass new financial variables
        return view('dashboard.super_admin', compact(
            'totalStudents', 'totalTeachers', 'totalStaff', 'totalCampuses', 'totalInstitutes', 
            'chartLabels', 'chartValues', 'currentSession',
            'presentCount', 'absentCount', 'lateCount',
            'totalEnrollment', 'newComers', 
            'expectedTotal', 'collectedTotal', 'remainingToCollect', 'paidCount', 'unpaidCount', 'installmentStats',
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