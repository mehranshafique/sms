<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\V1\Chatbot\VerificationController;
use App\Http\Controllers\Api\V1\Chatbot\StatsController;
use App\Http\Controllers\Api\V1\Chatbot\HomeworkController;
use App\Http\Controllers\Api\V1\Chatbot\FinanceController;
use App\Http\Controllers\Api\V1\Chatbot\CalendarController;
use App\Http\Controllers\Api\V1\Chatbot\RequestController;
use App\Http\Controllers\Api\V1\Chatbot\ChatbotAuthController;
use App\Http\Controllers\Api\V1\Chatbot\PickupController;
use App\Http\Controllers\Api\V1\PickupScanController; // App Side
use App\Http\Controllers\Api\V1\Chatbot\ChatbotWebhookController;
use App\Http\Controllers\Api\V1\Chatbot\ChatbotInteractionController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Secure endpoints for Terminals (QR/NFC) and Chatbots.
|
*/

// Public / Auth
// Route::post('/login', [AuthApiController::class, 'login']);

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


/*
|--------------------------------------------------------------------------
| API Routes for External Chatbot
|--------------------------------------------------------------------------
|
| Required: Authorization: Bearer {token}
|
*/




// Public Chatbot Webhook (No Auth Middleware, as providers call this)
Route::post('/v1/chatbot/webhook/{provider}', [ChatbotWebhookController::class, 'handle']);
Route::get('/v1/chatbot/webhook/{provider}', [ChatbotWebhookController::class, 'handle']); // Fallback for GET-based webhooks (Mobishastra)

Route::post('/v1/chatbot/webhook', [ChatbotInteractionController::class, 'handleWebhook']);

Route::prefix('v1')->group(function () {

    // --- CHATBOT ROUTES ---
    Route::prefix('chatbot')->middleware(['auth:sanctum', 'tenant.api'])->group(function () {
        // Auth & OTP
        Route::post('/verify-student', [VerificationController::class, 'verifyStudent']);
        Route::post('/verify-staff', [VerificationController::class, 'verifyStaff']);
        Route::post('/auth/request-otp', [ChatbotAuthController::class, 'requestOtp']);
        
        // Features
        Route::get('/institution/summary', [StatsController::class, 'getInstitutionSummary']);
        Route::get('/student/balance', [StatsController::class, 'getStudentBalance']);
        Route::get('/student/result', [StatsController::class, 'getStudentResult']);
        Route::get('/student/homework', [HomeworkController::class, 'getLatestHomework']);
        Route::get('/student/fees', [FinanceController::class, 'getMiscFees']);
        Route::get('/institution/events', [CalendarController::class, 'getUpcomingEvents']);
        Route::post('/student/derogation', [RequestController::class, 'submitDerogation']);
        Route::post('/student/request', [RequestController::class, 'submitRequest']);
        
        // Pickup QR Generation (Requires OTP)
        Route::post('/pickup/generate-qr', [PickupController::class, 'generateQr']);
    });

    // --- MOBILE APP ROUTES (Scanning) ---
    // Note: Use same middleware or specific app middleware
    Route::prefix('pickup')->middleware(['auth:sanctum', 'tenant.api'])->group(function () {
        Route::post('/scan', [PickupScanController::class, 'scan']);
    });

});