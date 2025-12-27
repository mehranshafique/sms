<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\HeadOfficersController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentEnrollmentController; 
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamMarkController;
use App\Http\Controllers\StudentPromotionController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\InstitutionContextController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SmsTemplateController; // Add this

// New Module Controllers
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\VotingController;
use App\Http\Controllers\StudentVotingController;
use App\Http\Controllers\StudentNoticeController;
// Finance Controllers
use App\Http\Controllers\Finance\FeeTypeController;
use App\Http\Controllers\Finance\FeeStructureController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\PaymentController;

// Middleware Imports
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use App\Http\Middleware\CheckModuleAccess; 
use App\Http\Controllers\LocationController;

Route::redirect('/','/login' );

Route::get('/change-language', function (\Illuminate\Http\Request $request) {
    $locale = $request->query('language');
    if (!in_array($locale, ['en', 'fr'])) abort(400);
    app()->setLocale($locale);
    session(['locale' => $locale]);
    return redirect()->back();
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    
    // =========================================================================
    // CORE SYSTEM (Access Control, Infrastructure, Core Student Data)
    // =========================================================================
    
    Route::resource('roles', RolesController::class);
    
    Route::prefix('modules')->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('modules.index');
        Route::post('/', [ModuleController::class, 'store'])->name('modules.store');
        Route::get('/{module}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
        Route::put('/{module}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
    });

    Route::prefix('permissions')->group(function(){
        Route::get('/{id}', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });

    Route::prefix('roles')->group(function(){
        Route::get('{role}/assign-permissions', [RolePermissionController::class, 'edit'])->name('roles.assign-permissions');
        Route::post('{role}/assign-permissions', [RolePermissionController::class, 'update'])->name('roles.update-permissions');
    });

    // Infrastructure
    Route::delete('institutes/bulk-delete', [InstituteController::class, 'bulkDelete'])->name('institutes.bulkDelete');
    Route::resource('institutes', InstituteController::class);
    Route::get('institution/switch/{id}', [InstitutionContextController::class, 'switch'])->name('institution.switch');

    Route::delete('campuses/bulk-delete', [CampusController::class, 'bulkDelete'])->name('campuses.bulkDelete');
    Route::resource('campuses', CampusController::class);

    Route::post('header-officers/bulk-delete', [HeadOfficersController::class, 'bulkDelete'])->name('header-officers.bulkDelete');
    Route::resource('header-officers', HeadOfficersController::class);
    
    Route::get('students/get-sections', [StudentController::class, 'getSections'])->name('students.get_sections');
    Route::resource('students', StudentController::class);

    // =========================================================================
    // MODULE: COMMUNICATION (Notices)
    // =========================================================================
    Route::middleware([CheckModuleAccess::class . ':communication'])->group(function () {
        Route::resource('notices', NoticeController::class);
    });
    // STUDENT NOTICES
    Route::get('/my-notices', [StudentNoticeController::class, 'index'])->name('student.notices.index');
    Route::get('/my-notices/{notice}', [StudentNoticeController::class, 'show'])->name('student.notices.show');

    // =========================================================================
    // MODULE: VOTING & ELECTIONS
    // =========================================================================

    Route::get('/my-elections', [StudentVotingController::class, 'index'])->name('student.elections.index');
    Route::get('/my-elections/{election}', [StudentVotingController::class, 'show'])->name('student.elections.show');
    Route::post('/my-elections/{election}/vote', [StudentVotingController::class, 'vote'])->name('student.elections.vote');

    Route::middleware([CheckModuleAccess::class . ':voting'])->group(function () {
        // Election Management
        Route::post('elections/{election}/positions', [ElectionController::class, 'addPosition'])->name('elections.addPosition');
        Route::post('elections/{election}/candidates', [ElectionController::class, 'addCandidate'])->name('elections.addCandidate');
        Route::delete('elections/candidates/{candidate}', [ElectionController::class, 'destroyCandidate'])->name('elections.destroyCandidate');
        
        // NEW: Publish and Close Routes
        Route::post('elections/{election}/publish', [ElectionController::class, 'publish'])->name('elections.publish');
        Route::post('elections/{election}/close', [ElectionController::class, 'close'])->name('elections.close');

        Route::resource('elections', ElectionController::class);
        
        // Voting Device Endpoints
        Route::post('voting/identify', [VotingController::class, 'identifyVoter'])->name('voting.identify');
        Route::post('voting/cast', [VotingController::class, 'castVote'])->name('voting.cast');
    });

    // =========================================================================
    // MODULE: ACADEMICS
    // =========================================================================
    Route::middleware([CheckModuleAccess::class . ':academics'])->group(function () {
        Route::resource('academic-sessions', AcademicSessionController::class);
        
        Route::post('grade-levels/bulk-delete', [GradeLevelController::class, 'bulkDelete'])->name('grade-levels.bulkDelete');
        Route::resource('grade-levels', GradeLevelController::class);

        Route::post('class-sections/bulk-delete', [ClassSectionController::class, 'bulkDelete'])->name('class-sections.bulkDelete');
        Route::resource('class-sections', ClassSectionController::class);

        Route::post('subjects/bulk-delete', [SubjectController::class, 'bulkDelete'])->name('subjects.bulkDelete');
        Route::resource('subjects', SubjectController::class);

        Route::get('timetables/print-filtered', [TimetableController::class, 'printFiltered'])->name('timetables.print_filtered');
        Route::get('timetables/routine', [TimetableController::class, 'classRoutine'])->name('timetables.routine');
        Route::post('timetables/bulk-delete', [TimetableController::class, 'bulkDelete'])->name('timetables.bulkDelete');
        Route::get('timetables/{timetable}/print', [TimetableController::class, 'print'])->name('timetables.print');
        Route::get('timetables/{timetable}/download', [TimetableController::class, 'downloadPdf'])->name('timetables.download');
        Route::resource('timetables', TimetableController::class);
        
        Route::post('enrollments/bulk-delete', [StudentEnrollmentController::class, 'bulkDelete'])->name('enrollments.bulkDelete');
        Route::resource('enrollments', StudentEnrollmentController::class);
        
        // Attendance
        Route::get('attendance/create', [StudentAttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [StudentAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/report', [StudentAttendanceController::class, 'report'])->name('attendance.report');
        
        // Promotions
        Route::get('promotions', [StudentPromotionController::class, 'index'])->name('promotions.index');
        Route::post('promotions', [StudentPromotionController::class, 'store'])->name('promotions.store');
    });

    // =========================================================================
    // MODULE: EXAMINATIONS
    // =========================================================================
    Route::middleware([CheckModuleAccess::class . ':examinations'])->group(function () {
        Route::post('exams/bulk-delete', [ExamController::class, 'bulkDelete'])->name('exams.bulkDelete');
        Route::post('exams/{exam}/finalize', [ExamController::class, 'finalize'])->name('exams.finalize');
        Route::get('exams/{exam}/print-result', [ExamController::class, 'printClassResult'])->name('exams.print_result');
        Route::resource('exams', ExamController::class);
        
        // Marks Entry
        Route::get('marks/create', [ExamMarkController::class, 'create'])->name('marks.create');
        Route::post('marks', [ExamMarkController::class, 'store'])->name('marks.store');
        Route::get('marks/get-classes', [ExamMarkController::class, 'getClasses'])->name('marks.get_classes');
        Route::get('marks/get-subjects', [ExamMarkController::class, 'getSubjects'])->name('marks.get_subjects');
    });
    
    // Student Result View
    Route::middleware([CheckModuleAccess::class . ':examinations'])->get('my-marks', [ExamMarkController::class, 'myMarks'])->name('marks.my_marks');

    // =========================================================================
    // MODULE: HR (Staff)
    // =========================================================================
    Route::middleware([CheckModuleAccess::class . ':hr'])->group(function () {
        Route::resource('staff', StaffController::class);
    });

    // =========================================================================
    // MODULE: FINANCE
    // =========================================================================
    Route::middleware([CheckModuleAccess::class . ':finance'])->prefix('finance')->group(function () {
        Route::resource('fee-types', FeeTypeController::class);
        Route::resource('fees', FeeStructureController::class);
        
        // Invoices
        Route::get('invoices/get-sections', [InvoiceController::class, 'getClassSections'])->name('invoices.get_sections');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::resource('invoices', InvoiceController::class);
        
        // Payments
        Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        
        Route::middleware([RoleMiddleware::class . ':Super Admin|Head Officer'])->group(function () {
             Route::get('platform-invoices', [SubscriptionController::class, 'invoices'])->name('subscriptions.invoices');
             Route::get('platform-invoices/{id}', [SubscriptionController::class, 'showInvoice'])->name('subscriptions.invoices.show');
             Route::get('platform-invoices/{id}/print', [SubscriptionController::class, 'printInvoice'])->name('subscriptions.invoices.print');
             Route::get('platform-invoices/{id}/download', [SubscriptionController::class, 'downloadInvoicePdf'])->name('subscriptions.invoices.download');
        });
        // --- SUBSCRIPTIONS (Main Admin Only) ---
        Route::middleware([RoleMiddleware::class . ':Super Admin'])->group(function () {
            // PACKAGES
            Route::get('packages', [SubscriptionController::class, 'indexPackages'])->name('packages.index');
            Route::post('packages', [SubscriptionController::class, 'storePackage'])->name('packages.store');
            Route::get('packages/{package}/edit', [SubscriptionController::class, 'editPackage'])->name('packages.edit');
            Route::put('packages/{package}', [SubscriptionController::class, 'updatePackage'])->name('packages.update');
            Route::delete('packages/{package}', [SubscriptionController::class, 'destroyPackage'])->name('packages.destroy');

            // SUBSCRIPTIONS
            Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
            Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
            Route::get('subscriptions/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('subscriptions.edit');
            Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
        });
    });

    // =========================================================================
    // SETTINGS & CONFIGURATION
    // =========================================================================
    
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/update', [SettingsController::class, 'update'])->name('settings.update');
    });
    
    // Tracking / Audit Logs
    Route::resource('audit-logs', \App\Http\Controllers\AuditLogController::class)
        ->only(['index'])
        ->middleware([RoleMiddleware::class . ':Super Admin']);

    // Configuration Module
    Route::prefix('configuration')
        ->middleware([RoleMiddleware::class . ':Super Admin|Head Officer'])
        ->group(function () {
            Route::get('/', [ConfigurationController::class, 'index'])->name('configuration.index');
            Route::post('/smtp', [ConfigurationController::class, 'updateSmtp'])->name('configuration.smtp.update');
            Route::post('/smtp/test', [ConfigurationController::class, 'testSmtp'])->name('configuration.smtp.test');
            Route::post('/sms', [ConfigurationController::class, 'updateSms'])->name('configuration.sms.update');
            Route::post('/school-year', [ConfigurationController::class, 'updateSchoolYear'])->name('configuration.year.update');
            
            // SMS Templates Management
            Route::get('/sms-templates', [SmsTemplateController::class, 'index'])->name('sms_templates.index');
            Route::put('/sms-templates/{id}', [SmsTemplateController::class, 'update'])->name('sms_templates.update');
            Route::post('/sms-templates/override', [SmsTemplateController::class, 'override'])->name('sms_templates.override');

            Route::middleware([RoleMiddleware::class . ':Super Admin'])->group(function () {
                Route::post('/modules', [ConfigurationController::class, 'updateModules'])->name('configuration.modules.update');
                Route::post('/recharge', [ConfigurationController::class, 'recharge'])->name('configuration.recharge');
            });
        });
    
    Route::middleware(['auth'])->prefix('locations')->name('locations.')->group(function() {
        Route::get('countries', [LocationController::class, 'countries'])->name('countries');
        Route::get('states', [LocationController::class, 'states'])->name('states');
        Route::get('cities', [LocationController::class, 'cities'])->name('cities');
    });
});

require __DIR__.'/auth.php';