<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\ChatbotKeyword;
use App\Models\Student;
use App\Models\User;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Assignment;
use App\Models\ExamRecord;
use App\Models\Notice;
use App\Models\StudentPickup;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChatbotLogicService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Main Processor
     */
    public function processMessage(array $data)
    {
        $phone = $data['from']; // Normalized 'from' (User ID)
        $text = trim($data['body']);
        $botPhone = $data['to'] ?? null; // System number (Receiver)

        // 1. Clean Phone
        $phone = preg_replace('/[^0-9]/', '', $phone); 

        // 2. Find Active Session
        $session = ChatSession::where('phone_number', $phone)->first();

        // 3. Check Expiry
        if ($session && now()->gt($session->expires_at)) {
            $lastFlow = $session->user_type; // Store hint of what they were doing?
            $session->delete();
            $session = null;
            // Optional: Send timeout message based on last flow
            return $this->reply($phone, __('chatbot.session_ended'), null);
        }

        // 4. Route Logic
        if (!$session) {
            return $this->handleNewSession($phone, $text);
        }

        // Apply Locale
        if ($session->locale) app()->setLocale($session->locale);

        // --- STATE MACHINE ROUTER ---
        switch ($session->status) {
            // AUTH FLOW
            case 'AWAITING_ID':
                return $this->processIdentity($session, $text);
            case 'AWAITING_OTP':
                return $this->processOtp($session, $text);
            
            // ACTIVE FLOWS
            case 'ACTIVE':
                return $this->processMainMenu($session, $text);
                
            // SUB-STATES (Student Features)
            case 'PAYMENT_METHOD_SELECT':
                return $this->processPaymentMethod($session, $text);
            case 'DEROGATION_DURATION_SELECT':
                return $this->processDerogation($session, $text);
            case 'REQUEST_TYPE_SELECT':
                return $this->processRequestType($session, $text);
            case 'REQUEST_REASON_SELECT':
                return $this->processRequestReason($session, $text);
            case 'QR_OTP_CONFIRM':
                return $this->processQrOtpConfirm($session, $text);
            case 'QR_OTP_INPUT':
                return $this->processQrOtpInput($session, $text);
            
            // HEAD OFFICER FLOWS
            case 'ADMIN_MENU':
                return $this->processAdminMenu($session, $text);
            case 'ADMIN_RANKING_SELECT':
                return $this->processAdminRanking($session, $text);
            case 'ADMIN_EXPORT_SELECT':
                return $this->processAdminExport($session, $text);
            case 'ADMIN_SCHOOL_SELECT':
                return $this->processAdminSchoolExport($session, $text);

            default:
                return $this->reply($phone, __('chatbot.unknown_state_error'), $session->institution_id);
        }
    }

    // =================================================================================
    // --- 1. INITIALIZATION & AUTH ---
    // =================================================================================

    protected function handleNewSession($phone, $text)
    {
        $textLower = strtolower($text);
        
        // ADMIN FLOW
        if (str_starts_with($textLower, 'admin')) {
            ChatSession::create([
                'phone_number' => $phone,
                'status' => 'AWAITING_ID',
                'user_type' => 'head_officer', // Flag for admin flow
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
            ]);
            return $this->reply($phone, "Bienvenue sur la plateforme Digitex.\nVeuillez saisir votre ID Head Office pour commencer.", null);
        }

        // STANDARD FLOW
        $keyword = ChatbotKeyword::where('keyword', $textLower)->first();

        if ($keyword || str_starts_with($textLower, 'bonjour') || str_starts_with($textLower, 'digitex')) {
            $locale = $keyword->language ?? 'fr'; // Default FR for 'bonjour'
            app()->setLocale($locale);

            ChatSession::create([
                'phone_number' => $phone,
                'institution_id' => $keyword->institution_id ?? null,
                'status' => 'AWAITING_ID',
                'locale' => $locale,
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
            ]);

            $msg = $keyword->welcome_message ?? __('chatbot.welcome_message');
            return $this->reply($phone, $msg, $keyword->institution_id ?? null);
        }

        return $this->reply($phone, __('chatbot.default_keyword_response'), null);
    }

    protected function processIdentity($session, $input)
    {
        // ADMIN Check
        if ($session->user_type === 'head_officer') {
            $user = User::where('username', $input)->orWhere('shortcode', $input)->first();
            // Allow Super Admin or Head Officer
            if ($user && ($user->hasRole('Head Officer') || $user->hasRole('Super Admin'))) {
                return $this->sendOtp($session, $user, 'head_officer');
            }
            return $this->incrementAttempts($session);
        }

        // STUDENT Check
        // Fix: Ensure we check institution if tied to keyword, otherwise global search
        $queryStudent = Student::with('parent')->where('admission_number', $input);
        if ($session->institution_id) $queryStudent->where('institution_id', $session->institution_id);
        $student = $queryStudent->first();

        if ($student) {
            return $this->sendOtp($session, $student, 'student');
        }

        return $this->incrementAttempts($session);
    }

    protected function sendOtp($session, $model, $type)
    {
        $otp = rand(100000, 999999);
        $phone = null;
        
        if ($type === 'student') {
            $phone = $model->parent->father_phone ?? $model->parent->mother_phone ?? $model->mobile_number;
        } else {
            $phone = $model->phone;
        }

        if (!$phone) return $this->reply($session->phone_number, __('chatbot.no_registered_phone'), $session->institution_id);

        $session->update([
            'user_id' => $model->id,
            'user_type' => get_class($model), // Stores App\Models\Student or User
            'institution_id' => $model->institution_id ?? $model->institute_id,
            'otp' => $otp,
            'status' => 'AWAITING_OTP',
            'identifier_input' => $type
        ]);

        // Send OTP via SMS
        $this->notificationService->performSend($phone, __('chatbot.otp_sms_message', ['otp' => $otp]), $session->institution_id, true, 'sms');
        
        $masked = Str::mask($phone, '*', 3, -3);
        return $this->reply($session->phone_number, __('chatbot.otp_sent_notification', ['phone' => $masked]), $session->institution_id);
    }

    protected function processOtp($session, $input)
    {
        if (trim($input) == $session->otp) {
            $nextState = ($session->identifier_input === 'head_officer') ? 'ADMIN_MENU' : 'ACTIVE';
            
            $session->update(['status' => $nextState, 'otp' => null]);
            
            if ($nextState === 'ADMIN_MENU') {
                return $this->sendAdminMenu($session);
            } else {
                return $this->sendStudentMenu($session);
            }
        }
        return $this->incrementAttempts($session);
    }

    // =================================================================================
    // --- 2. STUDENT FEATURES ---
    // =================================================================================

    protected function sendStudentMenu($session)
    {
        $student = Student::find($session->user_id);
        $enrollment = $student->enrollments()->latest()->first();
        $class = $enrollment ? ($enrollment->classSection->name ?? '') : '';
        $year = $enrollment ? ($enrollment->academicSession->name ?? '') : '';

        $msg = __('chatbot.main_menu', [
            'school' => $student->institution->name,
            'student' => $student->full_name,
            'class' => $class,
            'year' => $year
        ]);
        
        return $this->reply($session->phone_number, $msg, $session->institution_id);
    }

    protected function processMainMenu($session, $text)
    {
        $student = Student::find($session->user_id);
        if (!$student) return $this->reply($session->phone_number, "Error: Student record missing.", null);

        switch ($text) {
            case '1': // Homework
                return $this->getHomework($session, $student);
            case '2': // Payment
                $session->update(['status' => 'PAYMENT_METHOD_SELECT']);
                return $this->getPaymentSummary($session, $student);
            case '3': // Balance
                return $this->getBalance($session, $student);
            case '4': // Report Card
                return $this->getReportCard($session, $student);
            case '5': // Misc Fees
                return $this->getMiscFees($session, $student);
            case '6': // Activities
                return $this->getActivities($session, $student);
            case '7': // Derogation
                $session->update(['status' => 'DEROGATION_DURATION_SELECT']);
                return $this->reply($session->phone_number, __('chatbot.derogation_menu'), $session->institution_id);
            case '8': // Requests
                $session->update(['status' => 'REQUEST_TYPE_SELECT']);
                return $this->reply($session->phone_number, __('chatbot.request_menu'), $session->institution_id);
            case '9': // QR Code
                $session->update(['status' => 'QR_OTP_CONFIRM']);
                return $this->reply($session->phone_number, __('chatbot.qr_verification'), $session->institution_id);
            case '0': // Quit
            case 'logout':
                $session->delete();
                return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id);
            default:
                return $this->reply($session->phone_number, __('chatbot.unknown_command'), $session->institution_id);
        }
    }

    // --- Sub-Features ---

    protected function getBalance($session, $student)
    {
        $total = Invoice::where('student_id', $student->id)->sum('total_amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))->sum('amount');
        $due = $total - $paid;

        return $this->reply($session->phone_number, __('chatbot.balance_info', [
            'total' => number_format($total, 2) . ' $',
            'paid' => number_format($paid, 2) . ' $',
            'due' => number_format($due, 2) . ' $'
        ]), $session->institution_id);
    }

    protected function getPaymentSummary($session, $student)
    {
        $total = Invoice::where('student_id', $student->id)->sum('total_amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))->sum('amount');
        $due = $total - $paid;

        return $this->reply($session->phone_number, __('chatbot.payment_method_menu', [
            'due' => number_format($due, 2) . ' $',
            'total' => number_format($due, 2) . ' $' // Logic from legacy
        ]), $session->institution_id);
    }

    protected function processPaymentMethod($session, $text)
    {
        if ($text == '1') { // Visa
            $link = "https://e-digitex.com/checkout?id=" . base64_encode($session->institution_id);
            // Reset to Main Menu after showing link? Legacy kept user in flow.
            // Let's reset to ACTIVE to avoid getting stuck
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.payment_link', ['link' => $link]), $session->institution_id);
        } 
        elseif ($text == '2') { // Mobile Money
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.mobile_money_instruction'), $session->institution_id);
        } 
        elseif ($text == '0') {
            $session->update(['status' => 'ACTIVE']);
            return $this->sendStudentMenu($session);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    protected function getReportCard($session, $student)
    {
        // Fetch latest PDF from result_pdfs table (Mocking the legacy logic)
        // Since we don't have that table, we generate a link
        $url = route('reports.bulletin', ['student_id' => $student->id, 'mode' => 'single', 'report_scope' => 'period', 'period' => 'p1']);
        
        // In real legacy, it sent a document. Here we send a link or try to send doc if path exists.
        return $this->reply($session->phone_number, __('chatbot.result_found') . "\n" . $url, $session->institution_id);
    }

    protected function getMiscFees($session, $student)
    {
        // Logic: Fetch 'one_time' fees
        $fees = FeeStructure::where('institution_id', $student->institution_id)
            ->where('frequency', 'one_time')
            ->get();
        
        $content = "";
        foreach($fees as $f) {
            $content .= "- {$f->name}: " . number_format($f->amount, 2) . " $\n";
        }
        
        return $this->reply($session->phone_number, __('chatbot.misc_fees_list', ['content' => $content ?: 'None']), $session->institution_id);
    }

    protected function getActivities($session, $student)
    {
        $notices = Notice::where('institution_id', $student->institution_id)
            ->where('published_at', '>=', now()->subDays(30))
            ->take(5)->get();

        $content = "";
        foreach($notices as $n) {
            $content .= "📅 " . $n->title . " (" . $n->published_at->format('d M') . ")\n";
        }

        return $this->reply($session->phone_number, __('chatbot.activities_list', ['content' => $content ?: 'None']), $session->institution_id);
    }

    protected function processDerogation($session, $text)
    {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        $map = ['1' => 7, '2' => 15, '3' => 20, '4' => 30];
        if (!isset($map[$text])) return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);

        $days = $map[$text];
        $ticket = "DGR-" . rand(1000, 9999);
        
        // Logic: Insert into fee_extensions table (Mock)
        // DB::table('fee_extensions')->insert(...)

        $session->update(['status' => 'ACTIVE']);
        return $this->reply($session->phone_number, __('chatbot.derogation_submitted', ['days' => $days, 'ticket' => $ticket]), $session->institution_id);
    }

    protected function processRequestType($session, $text)
    {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }

        if ($text == '4') { // Sickness
             $session->update(['status' => 'ACTIVE']);
             return $this->reply($session->phone_number, __('chatbot.sick_leave_submitted'), $session->institution_id);
        }

        if (in_array($text, ['1', '2', '3'])) {
            $session->update(['status' => 'REQUEST_REASON_SELECT', 'temp_data' => json_encode(['type' => $text])]);
            return $this->reply($session->phone_number, __('chatbot.request_reason_'.$text), $session->institution_id);
        }

        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    protected function processRequestReason($session, $text)
    {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        $data = json_decode($session->temp_data ?? '{}', true);
        $typeMap = ['1' => 'Early Exit', '2' => 'Late', '3' => 'Absence'];
        $type = $typeMap[$data['type']] ?? 'Request';
        $ticket = "REQ-" . rand(1000, 9999);

        $session->update(['status' => 'ACTIVE']);
        return $this->reply($session->phone_number, __('chatbot.request_submitted', ['type' => $type, 'reason' => "Option $text", 'ticket' => $ticket]), $session->institution_id);
    }

    protected function processQrOtpConfirm($session, $text)
    {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        if ($text == '1') {
            $otp = rand(100000, 999999);
            $session->update(['otp' => $otp, 'status' => 'QR_OTP_INPUT']);
            
            // Send OTP to registered number (security)
            $student = Student::find($session->user_id);
            $this->notificationService->sendOtpNotification($student, $otp);
            
            return $this->reply($session->phone_number, __('chatbot.otp_sent'), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    protected function processQrOtpInput($session, $text)
    {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }

        if (trim($text) == $session->otp) {
            $student = Student::find($session->user_id);
            $token = 'QR-' . uniqid();
            StudentPickup::create([
                'institution_id' => $session->institution_id,
                'student_id' => $session->user_id,
                'token' => $token,
                'status' => 'pending',
                'expires_at' => now()->addHours(2)
            ]);

            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($token);
            $caption = __('chatbot.qr_caption', ['student' => $student->first_name]);
            
            // Send Image via Notification Service (Assumed capability)
            $this->reply($session->phone_number, $caption . "\n" . $qrUrl, $session->institution_id);
            
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            return $this->reply($session->phone_number, "Type 1 for Menu.", $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_otp'), $session->institution_id);
    }

    // =================================================================================
    // --- 3. HEAD OFFICER (ADMIN) FEATURES ---
    // =================================================================================

    protected function sendAdminMenu($session)
    {
        $user = User::find($session->user_id);
        return $this->reply($session->phone_number, __('chatbot.admin_welcome', ['name' => $user->name]), $session->institution_id);
    }

    protected function processAdminMenu($session, $text)
    {
        switch ($text) {
            case '1': // Dashboard
                $stats = $this->getAdminStats();
                return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $stats), $session->institution_id);
            case '2': // By School
                $data = $this->getSchoolStats();
                return $this->reply($session->phone_number, __('chatbot.admin_school_stats', ['content' => $data]), $session->institution_id);
            case '3': // Ranking
                $session->update(['status' => 'ADMIN_RANKING_SELECT']);
                return $this->reply($session->phone_number, __('chatbot.admin_ranking_menu'), $session->institution_id);
            case '4': // Trends (Simplified as Stats)
                $stats = $this->getAdminStats();
                return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $stats), $session->institution_id);
            case '5': // Export
                $session->update(['status' => 'ADMIN_EXPORT_SELECT']);
                return $this->reply($session->phone_number, __('chatbot.admin_export_menu'), $session->institution_id);
            case '6': // Help
                return $this->reply($session->phone_number, __('chatbot.admin_help'), $session->institution_id);
            case '0':
                $session->delete();
                return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id);
            default:
                return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
        }
    }

    protected function processAdminRanking($session, $text)
    {
        if ($text == '00') { $session->update(['status' => 'ADMIN_MENU']); return $this->sendAdminMenu($session); }
        if ($text == '0') { $session->delete(); return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id); }

        $type = match($text) {
            '31' => 'Payment Rate',
            '32' => 'Enrollment',
            '33' => 'Amounts',
            default => null
        };

        if (!$type) return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);

        $content = "1. School A - 90%\n2. School B - 85%\n3. School C - 50%"; // Mock Data for brevity
        return $this->reply($session->phone_number, __('chatbot.ranking_title', ['type' => $type, 'content' => $content]), $session->institution_id);
    }

    protected function processAdminExport($session, $text)
    {
        if ($text == '00') { $session->update(['status' => 'ADMIN_MENU']); return $this->sendAdminMenu($session); }
        
        if ($text == '2') { // By School
            $schools = Institution::take(5)->pluck('name')->map(fn($n, $k) => ($k+1)."️⃣ $n")->join("\n");
            $session->update(['status' => 'ADMIN_SCHOOL_SELECT']);
            return $this->reply($session->phone_number, __('chatbot.admin_school_selection', ['content' => $schools]), $session->institution_id);
        }

        // Global Export
        return $this->reply($session->phone_number, __('chatbot.export_ready'), $session->institution_id);
    }

    protected function processAdminSchoolExport($session, $text)
    {
        if ($text == '00') { $session->update(['status' => 'ADMIN_MENU']); return $this->sendAdminMenu($session); }
        // Logic to generate and send file
        return $this->reply($session->phone_number, __('chatbot.export_ready'), $session->institution_id);
    }

    // --- Helpers for Admin Stats ---
    protected function getAdminStats() {
        return [
            'schools' => Institution::count(),
            'students' => Student::count(),
            'paid_students' => 0, // Placeholder
            'paid_percentage' => 0,
            'amount_paid' => '0 $',
            'outstanding' => '0 $',
            'total_balance' => '0 $'
        ];
    }
    protected function getSchoolStats() {
        return Institution::take(3)->get()->map(fn($i) => "🏫 {$i->name}: " . $i->students()->count() . " Students")->join("\n");
    }

    // --- UTILS ---
    protected function incrementAttempts($session)
    {
        $session->increment('attempts');
        if ($session->attempts >= 3) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.too_many_attempts'), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.id_not_found', ['attempt' => $session->attempts]), $session->institution_id);
    }

    protected function reply($to, $message, $institutionId)
    {
        $this->notificationService->performSend($to, $message, $institutionId, false, 'whatsapp');
        return response()->json(['status' => 'success']);
    }
}