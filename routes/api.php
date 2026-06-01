<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Chatbot Controllers
use App\Http\Controllers\Api\V1\Chatbot\RequestController;
use App\Http\Controllers\Api\V1\Chatbot\PickupController as ChatbotPickupController;
use App\Http\Controllers\Api\V1\Chatbot\ChatbotWebhookController;

// Mobile App / Hardware Controllers
use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\AppPickupController;
use App\Http\Controllers\Api\AuthApiController;

/*
|--------------------------------------------------------------------------
| API Routes V2
|--------------------------------------------------------------------------
*/

// --- MOBILE APP STAFF AUTHENTICATION ---
Route::post('/v1/login', [AuthApiController::class, 'login']);

// --- UNIVERSAL HARDWARE & APP SCANNER (NFC, RFID, QR) ---
Route::prefix('v1/hardware')->group(function () {
    // This single endpoint now handles Attendance, Fee Checks, Pickups, and Report Cards!
    Route::post('/attendance/scan', [AttendanceApiController::class, 'store']);
    // NEW: Fetch live attendance list for today
    Route::get('/attendance/today', [AttendanceApiController::class, 'getTodayScans']);
});

// --- TEACHER APP ROUTES (Requires Sanctum Token) ---
Route::prefix('v1/pickup')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/pending', [AppPickupController::class, 'getPendingPickups']);
    Route::post('/approve', [AppPickupController::class, 'approvePickup']);
});

// --- WHATSAPP CHATBOT ROUTES ---
Route::prefix('v1/chatbot')->group(function () {
    Route::post('/webhook/{provider}', [ChatbotWebhookController::class, 'handle']);
    Route::post('/generate-qr', [ChatbotPickupController::class, 'generateQr']);
});