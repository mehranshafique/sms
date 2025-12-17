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
use App\Http\Controllers\StudentEnrollmentController; // Added
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;

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

    // Campuses
    Route::delete('campuses/bulk-delete', [CampusController::class, 'bulkDelete'])->name('campuses.bulkDelete');
    Route::resource('campuses', CampusController::class);

    // Head Officers
    Route::post('header-officers/bulk-delete', [HeadOfficersController::class, 'bulkDelete'])->name('header-officers.bulkDelete');
    Route::resource('header-officers', HeadOfficersController::class);
    
    // Core Modules
    Route::resource('students', StudentController::class);
    
    // Student Enrollments (NEW)
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

    Route::post('timetables/bulk-delete', [TimetableController::class, 'bulkDelete'])->name('timetables.bulkDelete');
    Route::resource('timetables', TimetableController::class);
});

require __DIR__.'/auth.php';