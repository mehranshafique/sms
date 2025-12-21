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
use App\Http\Controllers\Finance\FeeStructureController;

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
    // Access Control
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

    // Institutes
    Route::delete('institutes/bulk-delete', [InstituteController::class, 'bulkDelete'])->name('institutes.bulkDelete');
    Route::resource('institutes', InstituteController::class);
    // Institution Context Switcher
    Route::get('institution/switch/{id}', [App\Http\Controllers\InstitutionContextController::class, 'switch'])->name('institution.switch');

    // Campuses
    Route::delete('campuses/bulk-delete', [CampusController::class, 'bulkDelete'])->name('campuses.bulkDelete');
    Route::resource('campuses', CampusController::class);

    // Head Officers
    Route::post('header-officers/bulk-delete', [HeadOfficersController::class, 'bulkDelete'])->name('header-officers.bulkDelete');
    Route::resource('header-officers', HeadOfficersController::class);
    
    // --- Core Modules ---
    
    // FIX: Define get-sections BEFORE resource to prevent 404 error
    Route::get('students/get-sections', [StudentController::class, 'getSections'])->name('students.get_sections');
    Route::resource('students', StudentController::class);
    
    // Student Enrollments
    Route::post('enrollments/bulk-delete', [StudentEnrollmentController::class, 'bulkDelete'])->name('enrollments.bulkDelete');
    Route::resource('enrollments', StudentEnrollmentController::class);

    Route::resource('staff', StaffController::class);
    Route::resource('academic-sessions', AcademicSessionController::class);
    
    // Academic Structure
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

    // Attendance
    Route::get('attendance/create', [StudentAttendanceController::class, 'create'])->name('attendance.create');
    Route::post('attendance', [StudentAttendanceController::class, 'store'])->name('attendance.store');
    Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    // Must be defined BEFORE Route::resource('attendance', ...)
    Route::get('attendance/report', [StudentAttendanceController::class, 'report'])->name('attendance.report');

    // Exams
    Route::post('exams/bulk-delete', [ExamController::class, 'bulkDelete'])->name('exams.bulkDelete');
    
    Route::post('exams/{exam}/finalize', [\App\Http\Controllers\ExamController::class, 'finalize'])->name('exams.finalize');
    Route::get('exams/{exam}/print-result', [\App\Http\Controllers\ExamController::class, 'printClassResult'])->name('exams.print_result');
    Route::resource('exams', ExamController::class);
    
    // Marks
    Route::get('my-marks', [\App\Http\Controllers\ExamMarkController::class, 'myMarks'])->name('marks.my_marks');
    // Exam Marks
    Route::get('marks/create', [ExamMarkController::class, 'create'])->name('marks.create');
    Route::post('marks', [ExamMarkController::class, 'store'])->name('marks.store');
    Route::get('marks/get-classes', [\App\Http\Controllers\ExamMarkController::class, 'getClasses'])->name('marks.get_classes');
    Route::get('marks/get-subjects', [\App\Http\Controllers\ExamMarkController::class, 'getSubjects'])->name('marks.get_subjects');

    // Promotions
    Route::get('promotions', [StudentPromotionController::class, 'index'])->name('promotions.index');
    Route::post('promotions', [StudentPromotionController::class, 'store'])->name('promotions.store');

    Route::prefix('settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::post('/update', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    });
    
    // Finance Module
    Route::prefix('finance')->group(function () {
        Route::resource('fee-types', \App\Http\Controllers\Finance\FeeTypeController::class);
        Route::resource('fees', FeeStructureController::class);
        
        // Invoices
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\Finance\InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/download', [\App\Http\Controllers\Finance\InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::resource('invoices', \App\Http\Controllers\Finance\InvoiceController::class);
        
        // Payments
        Route::get('payments/create', [\App\Http\Controllers\Finance\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [\App\Http\Controllers\Finance\PaymentController::class, 'store'])->name('payments.store');
    });
});

require __DIR__.'/auth.php';