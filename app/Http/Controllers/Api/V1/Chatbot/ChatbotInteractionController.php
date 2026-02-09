<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatbotKeyword;
use App\Models\Student;
use App\Models\Staff;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ChatbotInteractionController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Main Webhook Entry Point
     * Route: POST /api/v1/chatbot/webhook
     */
    public function handleWebhook(Request $request)
    {
        // 1. Extract Data (Adapt based on provider structure: Infobip/Meta/Twilio)
        // This example assumes a normalized input like { "from": "12345", "text": "hello" }
        // You might need a Transformer/Adapter layer here for different providers.
        
        $from = $request->input('from'); // Phone Number
        $text = trim(strtolower($request->input('text')));

        if (!$from || !$text) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload']);
        }

        // 2. Find or Create Session
        $session = ChatSession::where('phone_number', $from)->first();

        // 3. Check Timeout
        if ($session && now()->gt($session->expires_at)) {
            $session->delete();
            $session = null;
            // Optional: Send "Session expired" message
        }

        // 4. Process Logic based on State
        if (!$session) {
            return $this->handleNewSession($from, $text);
        }

        switch ($session->status) {
            case 'AWAITING_ID':
                return $this->processIdentity($session, $text);
            case 'AWAITING_OTP':
                return $this->processOtp($session, $text);
            case 'ACTIVE':
                return $this->processCommand($session, $text);
            default:
                return $this->reply($from, "Error: Unknown state. Type 'Reset' to start over.");
        }
    }

    // --- PHASE 1: KEYWORD CHECK ---
    protected function handleNewSession($from, $text)
    {
        // Check for Keywords
        $keyword = ChatbotKeyword::where('keyword', $text)->first();

        if ($keyword) {
            // Create pending session
            ChatSession::create([
                'phone_number' => $from,
                'institution_id' => $keyword->institution_id, // Might be null (Global)
                'status' => 'AWAITING_ID',
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15) // 15 min login window
            ]);

            $msg = $keyword->welcome_message ?? "👋 Welcome to E-Digitex! Please enter your **Student ID** or **Staff ID** to login.";
            return $this->reply($from, $msg);
        }

        return $this->reply($from, "👋 Hi! Type 'Bonjour' or 'Hello' to start.");
    }

    // --- PHASE 2: ID IDENTIFICATION ---
    protected function processIdentity($session, $inputId)
    {
        // Search in Student Table
        $student = Student::where('admission_number', $inputId)->first();
        if ($student) {
            return $this->sendOtp($session, $student, 'student');
        }

        // Search in Staff/User Table
        $user = User::where('username', $inputId)->orWhere('shortcode', $inputId)->first();
        if ($user) {
            return $this->sendOtp($session, $user, 'staff');
        }

        $session->increment('attempts');
        if ($session->attempts >= 3) {
            $session->delete();
            return $this->reply($session->phone_number, "🚫 Too many failed attempts. Session ended.");
        }

        return $this->reply($session->phone_number, "❌ ID not found. Please try again (Attempt {$session->attempts}/3).");
    }

    // --- PHASE 3: OTP GENERATION ---
    protected function sendOtp($session, $model, $type)
    {
        $otp = rand(100000, 999999);
        $phone = $type === 'student' ? $model->parent_phone_primary : $model->phone; // Logic to get correct mobile

        // Update Session
        $session->update([
            'user_type' => get_class($model),
            'user_id' => $model->id,
            'institution_id' => $model->institution_id ?? $session->institution_id,
            'identifier_input' => $type === 'student' ? $model->admission_number : $model->username,
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'status' => 'AWAITING_OTP',
            'attempts' => 0
        ]);

        // Use Notification Service (SMS/WA)
        // Assuming you have a method to send raw message
        // $this->notificationService->sendRaw($phone, "Your OTP is: $otp");
        
        // For Demo/Testing, we might just reply (INSECURE - ONLY FOR DEV)
        // return $this->reply($session->phone_number, "TEST MODE: OTP is $otp. (Sent to registered mobile)");
        
        return $this->reply($session->phone_number, "🔒 OTP sent to your registered number. Please enter it now.");
    }

    // --- PHASE 4: VERIFICATION ---
    protected function processOtp($session, $inputOtp)
    {
        if (now()->gt($session->otp_expires_at)) {
            $session->delete();
            return $this->reply($session->phone_number, "⏳ OTP expired. Start again.");
        }

        if ($inputOtp == $session->otp) {
            // SUCCESS!
            // Set session timeout based on Institution Settings (or default 24h)
            $timeoutHours = 24; 
            
            $session->update([
                'status' => 'ACTIVE',
                'otp' => null, // Clear OTP
                'expires_at' => now()->addHours($timeoutHours)
            ]);

            return $this->reply($session->phone_number, "✅ Login Successful!\n\nMenu:\n1. Balance\n2. Homework\n3. Results\n4. Pickup QR");
        }

        $session->increment('attempts');
        return $this->reply($session->phone_number, "❌ Invalid OTP.");
    }

    // --- PHASE 5: ACTIVE SESSION COMMANDS ---
    protected function processCommand($session, $text)
    {
        $session->touch(); // Extend session on activity

        if ($text == 'logout') {
            $session->delete();
            return $this->reply($session->phone_number, "👋 Logged out successfully.");
        }

        // Map simple numbers/keywords to your existing API Controllers
        switch ($text) {
            case '1':
            case 'balance':
                return $this->forwardToController('StatsController@getStudentBalance', $session);
            case '2':
            case 'homework':
                return $this->forwardToController('HomeworkController@getLatestHomework', $session);
            case '4':
            case 'qr':
                // Auto-generate QR if they are logged in
                // We need to simulate a Request object or call logic directly
                return $this->reply($session->phone_number, "QR Generation logic here...");
            default:
                return $this->reply($session->phone_number, "❓ Unknown command.\n1. Balance\n2. Homework\nType 'logout' to exit.");
        }
    }

    // --- HELPERS ---
    protected function reply($to, $message)
    {
        return response()->json([
            'action' => 'reply',
            'to' => $to,
            'message' => $message
        ]);
    }

    /**
     * Internal dispatch to reuse your existing API logic
     */
    protected function forwardToController($controllerAction, $session)
    {
        // You would internally call the methods we built previously
        // constructing a Request object with $session->user_id
        // Example:
        // $request = new Request(['student_id' => $session->user_id]);
        // $response = app(StatsController::class)->getStudentBalance($request);
        // return $this->reply($session->phone_number, $response->getData()->data->message ?? 'Data: ...');
        
        return $this->reply($session->phone_number, "Fetching data for ID: " . $session->identifier_input);
    }
}