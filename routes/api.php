<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\AuthApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Secure endpoints for Terminals (QR/NFC) and Chatbots.
|
*/

// Public / Auth
Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // --- TERMINAL ATTENDANCE (Staff & Student) ---
    // Posted by physical devices
    Route::post('/attendance/terminal', [AttendanceApiController::class, 'store']);
    Route::post('/attendance/bulk', [AttendanceApiController::class, 'bulkStore']);

    // --- CHATBOT / PARENT APP READ-ONLY ---
    // Student Data
    Route::get('/student/{id}/profile', [StudentApiController::class, 'profile']);
    Route::get('/student/{id}/balance', [StudentApiController::class, 'financialStatus']);
    Route::get('/student/{id}/attendance', [StudentApiController::class, 'attendanceHistory']);
    Route::get('/student/{id}/results', [StudentApiController::class, 'latestResults']);

});