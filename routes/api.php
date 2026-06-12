<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\StudentApiController;

// Mobile / Hardware V1
use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\AppPickupController;
use App\Http\Controllers\Api\V1\PickupScanController;
use App\Http\Controllers\Api\V1\UserProfileApiController;
use App\Http\Controllers\Api\V1\MobileContextApiController;
use App\Http\Controllers\Api\V1\TimetableApiController;
use App\Http\Controllers\Api\V1\TeacherAttendanceApiController;
use App\Http\Controllers\Api\V1\MobileLookupApiController;
use App\Http\Controllers\Api\V1\StudentPortalApiController;

// Chatbot
use App\Http\Controllers\Api\V1\Chatbot\ChatbotWebhookController;
use App\Http\Controllers\Api\V1\Chatbot\ChatbotInteractionController;
use App\Http\Controllers\Api\V1\Chatbot\VerificationController;
use App\Http\Controllers\Api\V1\Chatbot\ChatbotAuthController;
use App\Http\Controllers\Api\V1\Chatbot\StatsController;
use App\Http\Controllers\Api\V1\Chatbot\HomeworkController;
use App\Http\Controllers\Api\V1\Chatbot\FinanceController;
use App\Http\Controllers\Api\V1\Chatbot\CalendarController;
use App\Http\Controllers\Api\V1\Chatbot\RequestController;
use App\Http\Controllers\Api\V1\Chatbot\PickupController as ChatbotPickupController;

/*
|--------------------------------------------------------------------------
| API Routes V1
|--------------------------------------------------------------------------
*/

// --- MOBILE APP AUTH & CONTEXT ---
Route::post('/v1/login', [AuthApiController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/logout', [MobileContextApiController::class, 'logout']);
    Route::get('/v1/me/context', [MobileContextApiController::class, 'context']);
    Route::post('/v1/update-fcm-token', [AuthApiController::class, 'updateFcmToken']);
});

// --- USER PROFILE ---
Route::middleware('auth:sanctum')->prefix('v1/profile')->group(function () {
    Route::get('/', [UserProfileApiController::class, 'getProfile']);
    Route::post('/update', [UserProfileApiController::class, 'updateProfile']);
});

// --- HARDWARE (NFC / RFID / QR GATES) ---
Route::prefix('v1/hardware')->group(function () {
    Route::post('/attendance/scan', [AttendanceApiController::class, 'store']);
    Route::post('/attendance/bulk', [AttendanceApiController::class, 'bulkStore']);
    Route::get('/attendance/today', [AttendanceApiController::class, 'getTodayScans']);
    Route::get('/attendance/absentees/today', [AttendanceApiController::class, 'getTodayAbsentees'])
        ->middleware('auth:sanctum');
    Route::get('/attendance/absentees', [AttendanceApiController::class, 'getTeacherClassAbsentees'])
        ->middleware('auth:sanctum');
    Route::post('/attendance/absentees/notify', [AttendanceApiController::class, 'notifyAbsentStudents'])
        ->middleware('auth:sanctum');
});

// Legacy terminal paths (backward compatible — hardware secret auth inside controller)
Route::post('/attendance/terminal', [AttendanceApiController::class, 'store']);
Route::post('/attendance/bulk', [AttendanceApiController::class, 'bulkStore']);

// --- LEGACY STUDENT READ API (chatbot integrations) ---
Route::middleware(['auth:sanctum', 'tenant.api'])->group(function () {
    Route::get('/student/{id}/profile', [StudentApiController::class, 'profile']);
    Route::get('/student/{id}/balance', [StudentApiController::class, 'financialStatus']);
    Route::get('/student/{id}/attendance', [StudentApiController::class, 'attendanceHistory']);
    Route::get('/student/{id}/results', [StudentApiController::class, 'latestResults']);
});

// --- TEACHER / STAFF PICKUP APP ---
Route::prefix('v1/pickup')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/count', [AppPickupController::class, 'getPendingCount']);
    Route::get('/pending', [AppPickupController::class, 'getPendingPickups']);
    Route::post('/approve', [AppPickupController::class, 'approvePickup']);
    Route::post('/generate-otp', [AppPickupController::class, 'generateOtp']);
    Route::post('/verify-otp', [AppPickupController::class, 'verifyOtp']);
    Route::post('/scan', [PickupScanController::class, 'scan'])->middleware('tenant.api');
});

// --- STUDENT MOBILE PORTAL ---
Route::prefix('v1/student')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/attendance', [StudentPortalApiController::class, 'getAttendance']);
    Route::get('/attendance-summary', [StudentPortalApiController::class, 'getAttendanceSummary']);
    Route::get('/fees', [StudentPortalApiController::class, 'getFees']);
    Route::get('/payment-options', [StudentPortalApiController::class, 'getPaymentOptions']);
    Route::post('/payment-proof', [StudentPortalApiController::class, 'submitPaymentProof']);
    Route::get('/homework', [StudentPortalApiController::class, 'getHomework']);
    Route::get('/results', [StudentPortalApiController::class, 'getResults']);
    Route::get('/lmd-transcript', [StudentPortalApiController::class, 'getLmdTranscript']);
    Route::get('/requests', [StudentPortalApiController::class, 'getRequests']);
    Route::post('/gate-pass', [StudentPortalApiController::class, 'generateGatePass']);
    Route::post('/requests', [StudentPortalApiController::class, 'submitRequest']);
});

// --- TIMETABLE ---
Route::prefix('v1/timetable')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/today', [TimetableApiController::class, 'today']);
    Route::get('/week', [TimetableApiController::class, 'week']);
});

// --- TEACHER MANUAL ATTENDANCE ---
Route::prefix('v1/teacher/attendance')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/classes', [TeacherAttendanceApiController::class, 'classes']);
    Route::get('/classes/{classSectionId}/subjects', [TeacherAttendanceApiController::class, 'subjects']);
    Route::get('/roster', [TeacherAttendanceApiController::class, 'roster']);
    Route::post('/mark', [TeacherAttendanceApiController::class, 'mark']);
});

// --- NOTICES & STAFF LOOKUP ---
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/v1/notices', [MobileLookupApiController::class, 'notices']);
    Route::get('/v1/staff/fee-lookup', [MobileLookupApiController::class, 'feeLookup']);
});

// --- CHATBOT WEBHOOKS (public — provider callbacks) ---
Route::prefix('v1/chatbot')->group(function () {
    Route::post('/webhook/{provider}', [ChatbotWebhookController::class, 'handle']);
    Route::get('/webhook/{provider}', [ChatbotWebhookController::class, 'handle']);
    Route::post('/webhook', [ChatbotInteractionController::class, 'handleWebhook']);

    Route::post('/generate-qr', [ChatbotPickupController::class, 'generateQr'])->middleware('auth:sanctum');
});

// --- CHATBOT REST API (Sanctum + tenant) ---
Route::prefix('v1/chatbot')->middleware(['auth:sanctum', 'tenant.api'])->group(function () {
    Route::post('/verify-student', [VerificationController::class, 'verifyStudent']);
    Route::post('/verify-staff', [VerificationController::class, 'verifyStaff']);
    Route::post('/auth/request-otp', [ChatbotAuthController::class, 'requestOtp']);

    Route::get('/institution/summary', [StatsController::class, 'getInstitutionSummary']);
    Route::get('/student/balance', [StatsController::class, 'getStudentBalance']);
    Route::get('/student/result', [StatsController::class, 'getStudentResult']);
    Route::get('/student/homework', [HomeworkController::class, 'getLatestHomework']);
    Route::get('/student/fees', [FinanceController::class, 'getMiscFees']);
    Route::get('/institution/events', [CalendarController::class, 'getUpcomingEvents']);
    Route::post('/student/derogation', [RequestController::class, 'submitDerogation']);
    Route::post('/student/request', [RequestController::class, 'submitRequest']);

    Route::post('/pickup/generate-qr', [ChatbotPickupController::class, 'generateQr']);
});
