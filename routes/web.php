<?php

use Illuminate\Support\Facades\Route;

// --- Controllers: Core & Infrastructure ---
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\HeadOfficersController;
use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\InstitutionContextController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\AuditLogController;

// --- Controllers: Academics ---
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\ClassSubjectController; // Added
use App\Http\Controllers\DepartmentController;   // Added

// --- Controllers: People ---
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentEnrollmentController; 
use App\Http\Controllers\UniversityEnrollmentController; // Added
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\StudentPromotionController;
use App\Http\Controllers\TransferController; 

// --- Controllers: Examinations ---
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamMarkController;
use App\Http\Controllers\ResultCardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExamScheduleController; 

// --- Controllers: Communication & Voting ---
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\StudentNoticeController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\VotingController;
use App\Http\Controllers\StudentVotingController;

// --- Controllers: Finance ---
use App\Http\Controllers\Finance\FeeTypeController;
use App\Http\Controllers\Finance\FeeStructureController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\FinancialReportController;
use App\Http\Controllers\Finance\StudentFinanceController; 
use App\Http\Controllers\SalaryStructureController; 
use App\Http\Controllers\Finance\BudgetController; 
use App\Http\Controllers\Finance\StudentBalanceController; // Added

// --- Middleware ---
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use App\Http\Middleware\CheckModuleAccess; 

// =========================================================================
// PUBLIC ROUTES
// =========================================================================

Route::redirect('/', '/login');

Route::get('/change-language', function (\Illuminate\Http\Request $request) {
    $locale = $request->query('language');
    if (!in_array($locale, ['en', 'fr'])) abort(400);
    app()->setLocale($locale);
    session(['locale' => $locale]);
    return redirect()->back();
});

// =========================================================================
// AUTHENTICATED ROUTES
// =========================================================================

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // =========================================================================
    // 1. CORE SYSTEM & ADMINISTRATION
    // =========================================================================
    
    // User Profile
    Route::get('profile', [UserProfileController::class, 'index'])->name('profile.index');
    Route::put('profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [UserProfileController::class, 'updatePassword'])->name('profile.password');

    // Access Control (Roles & Permissions)
    Route::resource('roles', RolesController::class);
    
    Route::prefix('modules')->name('modules.')->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('index');
        Route::post('/', [ModuleController::class, 'store'])->name('store');
        Route::get('/{module}/edit', [ModuleController::class, 'edit'])->name('edit');
        Route::put('/{module}', [ModuleController::class, 'update'])->name('update');
        Route::delete('/{module}', [ModuleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('permissions')->name('permissions.')->group(function(){
        Route::get('/{id}', [PermissionController::class, 'index'])->name('index');
        Route::post('/', [PermissionController::class, 'store'])->name('store');
        Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->group(function(){
        Route::get('{role}/assign-permissions', [RolePermissionController::class, 'edit'])->name('assign-permissions');
        Route::post('{role}/assign-permissions', [RolePermissionController::class, 'update'])->name('update-permissions');
    });

    // Infrastructure Management (Institutes, Campuses)
    Route::get('institutes/check-email', [App\Http\Controllers\InstituteController::class, 'checkEmail'])->name('institutes.check_email');
    Route::delete('institutes/bulk-delete', [InstituteController::class, 'bulkDelete'])->name('institutes.bulkDelete');
    Route::resource('institutes', InstituteController::class);
    Route::get('institution/switch/{id}', [InstitutionContextController::class, 'switch'])->name('institution.switch');
    
    Route::delete('campuses/bulk-delete', [CampusController::class, 'bulkDelete'])->name('campuses.bulkDelete');
    Route::resource('campuses', CampusController::class);

    Route::post('header-officers/bulk-delete', [HeadOfficersController::class, 'bulkDelete'])->name('header-officers.bulkDelete');
    Route::resource('header-officers', HeadOfficersController::class);

    // Locations (Helper)
    Route::prefix('locations')->name('locations.')->group(function() {
        Route::get('countries', [LocationController::class, 'countries'])->name('countries');
        Route::get('states', [LocationController::class, 'states'])->name('states');
        Route::get('cities', [LocationController::class, 'cities'])->name('cities');
    });

    // =========================================================================
    // 2. ACADEMICS MODULE
    // =========================================================================
    
    // Academic Sessions
    Route::middleware([CheckModuleAccess::class . ':academic_sessions'])->group(function () {
        Route::resource('academic-sessions', AcademicSessionController::class);
    });
    
    // Departments (University)
    Route::middleware([CheckModuleAccess::class . ':departments'])->group(function () {
        Route::resource('departments', DepartmentController::class);
    });

    // Grade Levels
    Route::middleware([CheckModuleAccess::class . ':grade_levels'])->group(function () {
        Route::post('grade-levels/bulk-delete', [GradeLevelController::class, 'bulkDelete'])->name('grade-levels.bulkDelete');
        Route::resource('grade-levels', GradeLevelController::class);
    });

    // Class Sections
    Route::middleware([CheckModuleAccess::class . ':class_sections'])->group(function () {
        Route::post('class-sections/bulk-delete', [ClassSectionController::class, 'bulkDelete'])->name('class-sections.bulkDelete');
        Route::resource('class-sections', ClassSectionController::class);
    });

    // Subjects
    Route::middleware([CheckModuleAccess::class . ':subjects'])->group(function () {
        Route::post('subjects/bulk-delete', [SubjectController::class, 'bulkDelete'])->name('subjects.bulkDelete');
        Route::resource('subjects', SubjectController::class);
    });

    // Class Course Allocation (Hybrid)
    Route::middleware([CheckModuleAccess::class . ':class_subjects'])->group(function () {
        Route::get('class-subjects', [ClassSubjectController::class, 'index'])->name('class-subjects.index');
        Route::post('class-subjects', [ClassSubjectController::class, 'store'])->name('class-subjects.store');
    });

    // Timetables
    Route::middleware([CheckModuleAccess::class . ':timetables'])->group(function () {
        // Helper Routes
        Route::get('timetables/check-availability', [TimetableController::class, 'checkAvailability'])->name('timetables.check_availability');
        Route::get('timetables/get-allocated-subjects', [TimetableController::class, 'getAllocatedSubjects'])->name('timetables.get_allocated_subjects');
        
        Route::get('timetables/print-filtered', [TimetableController::class, 'printFiltered'])->name('timetables.print_filtered');
        Route::get('timetables/routine', [TimetableController::class, 'classRoutine'])->name('timetables.routine');
        Route::post('timetables/bulk-delete', [TimetableController::class, 'bulkDelete'])->name('timetables.bulkDelete');
        Route::get('timetables/{timetable}/print', [TimetableController::class, 'print'])->name('timetables.print');
        Route::get('timetables/{timetable}/download', [TimetableController::class, 'downloadPdf'])->name('timetables.download');
        Route::resource('timetables', TimetableController::class);
    });
    
    // Assignments
    Route::middleware([CheckModuleAccess::class . ':assignments'])->group(function () {
        Route::get('assignments/get-subjects', [App\Http\Controllers\AssignmentController::class, 'getSubjects'])->name('assignments.get-subjects');
        Route::resource('assignments', App\Http\Controllers\AssignmentController::class);
    });

    // =========================================================================
    // 3. PEOPLE & HR MODULE
    // =========================================================================
    // Parent / Guardian Management
    Route::get('parents/check', [App\Http\Controllers\ParentController::class, 'check'])->name('parents.check');
    
    // Students
    Route::get('students/get-sections', [StudentController::class, 'getSections'])->name('students.get_sections');
    Route::resource('students', StudentController::class);
    
    // Student Transfer Routes
    Route::middleware([CheckModuleAccess::class . ':student_transfers'])->group(function () {
        Route::get('students/{student}/transfer', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('students/{student}/transfer', [TransferController::class, 'store'])->name('transfers.store');
        Route::get('students/{student}/transfer-certificate', [TransferController::class, 'printCertificate'])->name('transfers.print');
    });
    
    // Enrollments
    Route::middleware([CheckModuleAccess::class . ':enrollments'])->group(function () {
        Route::post('enrollments/bulk-delete', [StudentEnrollmentController::class, 'bulkDelete'])->name('enrollments.bulkDelete');
        Route::resource('enrollments', StudentEnrollmentController::class);
    });

    // NEW: University Enrollments
    Route::middleware([CheckModuleAccess::class . ':enrollments'])->prefix('university')->name('university.')->group(function () {
        Route::resource('enrollments', UniversityEnrollmentController::class);
    });

    // Student Promotions
    Route::middleware([CheckModuleAccess::class . ':student_promotion'])->group(function () {
        Route::get('promotions', [StudentPromotionController::class, 'index'])->name('promotions.index');
        Route::post('promotions', [StudentPromotionController::class, 'store'])->name('promotions.store');
    });

    // Student Attendance
    Route::middleware([CheckModuleAccess::class . ':student_attendance'])->group(function () {
        Route::get('attendance/create', [StudentAttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [StudentAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/report', [StudentAttendanceController::class, 'report'])->name('attendance.report');
        Route::get('attendance/print-report', [StudentAttendanceController::class, 'printReport'])->name('attendance.print_report');
    });

    // HR / Staff Management
    Route::middleware([CheckModuleAccess::class . ':staff'])->group(function () {
        Route::resource('staff', StaffController::class);

        // PAYROLL & SALARY ROUTES
        Route::get('payroll', [App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate', [App\Http\Controllers\PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('payroll/{payroll}/payslip', [App\Http\Controllers\PayrollController::class, 'payslip'])->name('payroll.payslip');
        
        // Salary Structure Management
        Route::get('salary-structures', [SalaryStructureController::class, 'index'])->name('salary-structures.index');
        Route::get('salary-structures/{staff}/edit', [SalaryStructureController::class, 'edit'])->name('salary-structures.edit');
        Route::put('salary-structures/{staff}', [SalaryStructureController::class, 'update'])->name('salary-structures.update');

        // Staff Attendance
        Route::resource('staff-attendance', StaffAttendanceController::class)->only(['index', 'create', 'store']);
    });

    // =========================================================================
    // 4. EXAMINATIONS & REPORTS MODULE
    // =========================================================================
    
    // Exams
    Route::middleware([CheckModuleAccess::class . ':exams'])->group(function () {
        Route::post('exams/bulk-delete', [ExamController::class, 'bulkDelete'])->name('exams.bulkDelete');
        Route::post('exams/{exam}/finalize', [ExamController::class, 'finalize'])->name('exams.finalize');
        
        // --- PRINT ROUTES ---
        // General Exam Result (Whole Class/All Subjects)
        Route::get('exams/{exam}/print-result', [ExamController::class, 'printClassResult'])->name('exams.print_result');
        // Specific Award List (Single Subject)
        Route::get('exams/print-award-list', [ExamMarkController::class, 'printAwardList'])->name('exams.print_award_list');

        Route::resource('exams', ExamController::class);
        
        // Result Cards
        Route::prefix('results')->name('results.')->group(function() {
            Route::get('/', [ResultCardController::class, 'index'])->name('index');
            Route::get('/print', [ResultCardController::class, 'print'])->name('print');
            // Helpers
            Route::get('/get-classes', [ResultCardController::class, 'getClasses'])->name('get_classes');
            Route::get('/get-students', [ResultCardController::class, 'getStudents'])->name('get_students');
        });
    });

    // Exam Schedules & Admit Cards
    Route::middleware([CheckModuleAccess::class . ':exam_schedules'])->group(function () {
        Route::get('exam-schedules', [ExamScheduleController::class, 'manage'])->name('exam-schedules.manage');
        Route::get('exam-schedules/view', [ExamScheduleController::class, 'index'])->name('exam-schedules.index'); // ADDED THIS LINE
        Route::get('exam-schedules/get-subjects', [ExamScheduleController::class, 'getSubjects'])->name('exam-schedules.get-subjects');
        Route::get('exam-schedules/get-students', [ExamScheduleController::class, 'getStudents'])->name('exam-schedules.get-students'); // Added missing route
        Route::get('exam-schedules/auto-generate', [ExamScheduleController::class, 'autoGenerate'])->name('exam-schedules.auto-generate'); // Added missing route
        Route::post('exam-schedules', [ExamScheduleController::class, 'store'])->name('exam-schedules.store');
        Route::post('exam-schedules/download-admit-cards', [ExamScheduleController::class, 'downloadAdmitCards'])->name('exam-schedules.download-admit-cards');
    });
    
    // Exam Marks
    Route::middleware([CheckModuleAccess::class . ':exam_marks'])->group(function () {
        Route::get('marks/create', [ExamMarkController::class, 'create'])->name('marks.create');
        Route::post('marks', [ExamMarkController::class, 'store'])->name('marks.store');
        
        // AJAX Helpers (NEW: Split Grade & Section)
        Route::get('marks/get-grades', [ExamMarkController::class, 'getGrades'])->name('marks.get_grades'); 
        Route::get('marks/get-sections', [ExamMarkController::class, 'getSections'])->name('marks.get_sections'); 
        Route::get('marks/get-subjects', [ExamMarkController::class, 'getSubjects'])->name('marks.get_subjects');
        Route::get('marks/get-students', [ExamMarkController::class, 'getStudents'])->name('marks.get_students');
        
        // Student View
        Route::get('my-marks', [ExamMarkController::class, 'myMarks'])->name('marks.my_marks');
    });

    // Academic Reports (Bulletins, Transcripts)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/bulletin', [ReportController::class, 'bulletin'])->name('bulletin');
        Route::get('/transcript', [ReportController::class, 'transcript'])->name('transcript');
    });

    // =========================================================================
    // 5. FINANCE MODULE 
    // =========================================================================
    
    Route::prefix('finance')->group(function () {
        
         // NEW: Student Finance Dashboard (Tabbed View)
        Route::get('student/{student}/dashboard', [StudentFinanceController::class, 'index'])
            ->name('finance.student.dashboard');
        
        // NEW: Student Balances Overview
        Route::get('balances', [StudentBalanceController::class, 'index'])->name('finance.balances.index');
        Route::get('balances/class/{id}', [StudentBalanceController::class, 'getClassDetails'])->name('finance.balances.class_details'); 


        // BUDGET MODULE
        // Categories
        Route::get('budgets/categories', [BudgetController::class, 'categories'])->name('budgets.categories');
        Route::post('budgets/categories', [BudgetController::class, 'storeCategory'])->name('budgets.categories.store');
        
        // Allocations & Index
        Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit'); // Added Edit
        Route::put('budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update'); // Added Update
        
        // Requests
        Route::get('budgets/requests', [BudgetController::class, 'fundRequests'])->name('budgets.requests');
        Route::post('budgets/requests/store', [BudgetController::class, 'storeFundRequest'])->name('budgets.requests.store');
        
        // Approvals (Using 'update' naming convention for clarity, maps to approveFundRequest)
        Route::post('budgets/requests/{id}/update', [BudgetController::class, 'approveFundRequest'])->name('budgets.requests.update');


        // Invoices - AJAX Routes
        Route::get('invoices/get-sections', [InvoiceController::class, 'getClassSections'])->name('invoices.get_sections');
        Route::get('invoices/get-students', [InvoiceController::class, 'getStudents'])->name('invoices.get_students'); 
        Route::get('invoices/get-fees', [InvoiceController::class, 'getFees'])->name('invoices.get_fees'); 
        Route::get('invoices/check-duplicates', [InvoiceController::class, 'checkDuplicates'])->name('invoices.check_duplicates'); 
        
        // Fee Settings
        Route::middleware([CheckModuleAccess::class . ':fee_types'])->group(function () {
            Route::resource('fee-types', FeeTypeController::class);
        });

        Route::middleware([CheckModuleAccess::class . ':fee_structures'])->group(function () {
            Route::get('fees/get-sections', [FeeStructureController::class, 'getClassSections'])->name('fees.get_sections'); 
            Route::get('reports/class-summary', [FinancialReportController::class, 'index'])->name('finance.reports.class_summary');
            Route::resource('fees', FeeStructureController::class);
        });
        
        // Invoices
        Route::middleware([CheckModuleAccess::class . ':invoices'])->group(function () {
            Route::get('invoices/get-sections', [InvoiceController::class, 'getClassSections'])->name('invoices.get_sections');
            Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
            Route::get('invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
            Route::resource('invoices', InvoiceController::class);
        });
        
        // Payments
        Route::middleware([CheckModuleAccess::class . ':payments'])->group(function () {
            Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
            Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        });
        
        // Platform Invoices (Super Admin View)
        Route::middleware([RoleMiddleware::class . ':Super Admin|Head Officer'])->group(function () {
             Route::get('platform-invoices', [SubscriptionController::class, 'invoices'])->name('subscriptions.invoices');
             Route::get('platform-invoices/{id}', [SubscriptionController::class, 'showInvoice'])->name('subscriptions.invoices.show');
             Route::get('platform-invoices/{id}/print', [SubscriptionController::class, 'printInvoice'])->name('subscriptions.invoices.print');
             Route::get('platform-invoices/{id}/download', [SubscriptionController::class, 'downloadInvoicePdf'])->name('subscriptions.invoices.download');
        });

        // Subscriptions & Packages (Super Admin Only)
        Route::middleware([RoleMiddleware::class . ':Super Admin'])->group(function () {
            // Packages
            Route::prefix('packages')->name('packages.')->group(function() {
                Route::get('/', [SubscriptionController::class, 'indexPackages'])->name('index');
                Route::post('/', [SubscriptionController::class, 'storePackage'])->name('store');
                Route::get('/{package}/edit', [SubscriptionController::class, 'editPackage'])->name('edit');
                Route::put('/{package}', [SubscriptionController::class, 'updatePackage'])->name('update');
                Route::delete('/{package}', [SubscriptionController::class, 'destroyPackage'])->name('destroy');
            });

            // Subscriptions
            Route::prefix('subscriptions')->name('subscriptions.')->group(function() {
                Route::get('/', [SubscriptionController::class, 'index'])->name('index');
                Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
                Route::post('/', [SubscriptionController::class, 'store'])->name('store');
                Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
                Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
            });
        });
    });

    // =========================================================================
    // 6. COMMUNICATION MODULE
    // =========================================================================
    
    Route::middleware([CheckModuleAccess::class . ':notices'])->group(function () {
        Route::resource('notices', NoticeController::class);
        // Student View
        Route::get('my-notices', [StudentNoticeController::class, 'index'])->name('student.notices.index');
        Route::get('my-notices/{notice}', [StudentNoticeController::class, 'show'])->name('student.notices.show');
    });

    // =========================================================================
    // 7. VOTING & ELECTIONS MODULE
    // =========================================================================

    // Student Interface
    Route::middleware([CheckModuleAccess::class . ':elections'])->group(function() {
        Route::get('/my-elections', [StudentVotingController::class, 'index'])->name('student.elections.index');
        Route::get('/my-elections/{election}', [StudentVotingController::class, 'show'])->name('student.elections.show');
        Route::post('/my-elections/{election}/vote', [StudentVotingController::class, 'vote'])->name('student.elections.vote');
    });

    // Admin Interface
    Route::middleware([CheckModuleAccess::class . ':elections'])->group(function () {
        Route::post('elections/{election}/positions', [ElectionController::class, 'addPosition'])->name('elections.addPosition');
        Route::post('elections/{election}/candidates', [ElectionController::class, 'addCandidate'])->name('elections.addCandidate');
        Route::delete('elections/candidates/{candidate}', [ElectionController::class, 'destroyCandidate'])->name('elections.destroyCandidate');
        Route::post('elections/{election}/publish', [ElectionController::class, 'publish'])->name('elections.publish');
        Route::post('elections/{election}/close', [ElectionController::class, 'close'])->name('elections.close');
        Route::resource('elections', ElectionController::class);
        
        // Voting Device Logic
        Route::post('voting/identify', [VotingController::class, 'identifyVoter'])->name('voting.identify');
        Route::post('voting/cast', [VotingController::class, 'castVote'])->name('voting.cast');
    });

    // =========================================================================
    // 8. CONFIGURATION & SETTINGS
    // =========================================================================
    
    // Global Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/update', [SettingsController::class, 'update'])->name('settings.update');
    });
    
    // Audit Logs (Super Admin)
    Route::resource('audit-logs', AuditLogController::class)->only(['index'])->middleware([RoleMiddleware::class . ':Super Admin']);

    // Institution Configuration
    Route::prefix('configuration')
        ->middleware([RoleMiddleware::class . ':Super Admin|Head Officer'])
        ->group(function () {
            Route::get('/', [ConfigurationController::class, 'index'])->name('configuration.index');
            Route::post('/smtp', [ConfigurationController::class, 'updateSmtp'])->name('configuration.smtp.update');
            Route::post('/smtp/test', [ConfigurationController::class, 'testSmtp'])->name('configuration.smtp.test');
            Route::post('/sms', [ConfigurationController::class, 'updateSms'])->name('configuration.sms.update');
            Route::post('/school-year', [ConfigurationController::class, 'updateSchoolYear'])->name('configuration.year.update');
            
            // SMS Templates
            Route::middleware([CheckModuleAccess::class . ':sms_templates'])->group(function() {
                Route::get('/sms-templates', [SmsTemplateController::class, 'index'])->name('sms_templates.index');
                Route::put('/sms-templates/{id}', [SmsTemplateController::class, 'update'])->name('sms_templates.update');
                Route::post('/sms-templates/override', [SmsTemplateController::class, 'override'])->name('sms_templates.override');
            });

            // Super Admin Actions
            Route::middleware([RoleMiddleware::class . ':Super Admin'])->group(function () {
                Route::post('/modules', [ConfigurationController::class, 'updateModules'])->name('configuration.modules.update');
                Route::post('/recharge', [ConfigurationController::class, 'recharge'])->name('configuration.recharge');
            });
        });

}); // End Auth Middleware Group

require __DIR__.'/auth.php';