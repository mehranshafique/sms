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
use App\Http\Controllers\StaffController;

Route::redirect('/','/login' );

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
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

    Route::resource('institutes', InstituteController::class);

    Route::resource('header-officers', HeadOfficersController::class);

    Route::resource('students', StudentController::class);

    Route::resource('staff', StaffController::class);

});



require __DIR__.'/auth.php';
