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
use App\Models\StudentEnrollment;
use App\Enums\CurrencySymbol;
use App\Enums\RoleEnum;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        try {
            $phone = $data['from']; 
            $text = trim($data['body']);
            
            // Clean Phone
            $phone = preg_replace('/[^0-9]/', '', $phone); 

            $session = ChatSession::where('phone_number', $phone)->first();

            // Timeout Check (30 mins)
            if ($session && now()->gt($session->expires_at)) {
                $session->delete();
                $session = null;
                return $this->reply($phone, __('chatbot.session_ended'), null);
            }

            // Route Logic
            if (!$session) {
                return $this->handleNewSession($phone, $text);
            }

            // Update timestamp & Locale
            $session->update(['last_interaction_at' => now(), 'expires_at' => now()->addMinutes(30)]);
            if ($session->locale) app()->setLocale($session->locale);

            // --- STATE MACHINE ROUTER ---
            switch ($session->status) {
                case 'AWAITING_ID':
                    return $this->processIdentity($session, $text);
                case 'AWAITING_OTP':
                    return $this->processOtp($session, $text);
                
                case 'ACTIVE':
                    if ($session->user_type === 'head_officer') {
                        return $this->processAdminMenu($session, $text);
                    }
                    return $this->processStudentMenu($session, $text);
                    
                // Student Sub-flows
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
                
                // Admin Sub-flows
                case 'ADMIN_RANKING_SELECT':
                    return $this->processAdminRanking($session, $text);
                case 'ADMIN_EXPORT_SELECT':
                    return $this->processAdminExport($session, $text);
                case 'ADMIN_SCHOOL_SELECT':
                    return $this->processAdminSchoolExport($session, $text);

                default:
                    $session->update(['status' => 'ACTIVE']); // Self-heal
                    return $this->reply($phone, __('chatbot.unknown_state_error'), $session->institution_id);
            }

        } catch (\Throwable $e) {
            Log::error("Chatbot Critical Error: " . $e->getMessage());
            if (isset($data['from'])) {
                $p = preg_replace('/[^0-9]/', '', $data['from']);
                return $this->reply($p, __('chatbot.system_error'), null);
            }
            return response()->json(['status' => 'error']);
        }
    }

    // --- 1. START ---
    protected function handleNewSession($phone, $text)
    {
        $text = strtolower($text);
        
        // Admin Flow
        if (str_starts_with($text, 'admin')) {
            ChatSession::create([
                'phone_number' => $phone,
                'status' => 'AWAITING_ID',
                'user_type' => 'head_officer', 
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null,
                'locale' => 'fr' // Default to French
            ]);
            return $this->reply($phone, __('chatbot.admin_welcome_prompt'), null);
        }

        // Student Flow
        $keyword = ChatbotKeyword::where('keyword', $text)->first();
        if ($keyword || in_array($text, ['bonjour', 'hello', 'digitex', 'menu', 'start'])) {
            $locale = $keyword->language ?? 'fr'; 
            app()->setLocale($locale);

            ChatSession::create([
                'phone_number' => $phone,
                'institution_id' => $keyword->institution_id ?? null,
                'status' => 'AWAITING_ID',
                'locale' => $locale,
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null, 
                'user_type' => 'student'
            ]);

            $msg = $keyword->welcome_message ?? __('chatbot.welcome_message');
            return $this->reply($phone, $msg, $keyword->institution_id ?? null);
        }

        return $this->reply($phone, __('chatbot.keywords_not_found'), null);
    }

    // --- 2. AUTH ---
    protected function processIdentity($session, $input)
    {
        // Admin Auth
        if ($session->user_type === 'head_officer') {
            $user = User::where('username', $input)->orWhere('shortcode', $input)->first();
            if ($user && ($user->hasRole(RoleEnum::HEAD_OFFICER->value) || $user->hasRole(RoleEnum::SUPER_ADMIN->value) || $user->hasRole(RoleEnum::SCHOOL_ADMIN->value))) {
                return $this->sendOtp($session, $user, 'head_officer');
            }
            return $this->incrementAttempts($session, __('chatbot.admin_id_invalid'));
        }

        // Student Auth
        $student = Student::with('parent')->where('admission_number', $input)->first();
        if ($student) {
            return $this->sendOtp($session, $student, 'student');
        }

        return $this->incrementAttempts($session, __('chatbot.student_id_invalid'));
    }

    protected function sendOtp($session, $model, $type)
    {
        $otp = rand(100000, 999999);
        
        $phone = null;
        if ($type === 'student') {
            $phone = $model->parent->father_phone ?? $model->parent->mother_phone ?? $model->parent->guardian_phone ?? $model->mobile_number;
        } else {
            $phone = $model->phone;
        }

        if (!$phone) return $this->reply($session->phone_number, __('chatbot.no_registered_phone'), $session->institution_id);
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $session->update([
            'user_id' => $model->id,
            'user_type' => get_class($model),
            'institution_id' => $model->institution_id ?? $model->institute_id,
            'otp' => $otp,
            'status' => 'AWAITING_OTP',
            'identifier_input' => $type 
        ]);

        // FORCE SMS
        $this->notificationService->performSend($cleanPhone, __('chatbot.otp_sms_message', ['otp' => $otp]), $session->institution_id, true, 'sms');
        
        $masked = Str::mask($cleanPhone, '*', 3, -3);
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
        return $this->incrementAttempts($session, __('chatbot.invalid_otp'));
    }

    // --- 3. STUDENT MENU ---
    protected function sendStudentMenu($session)
    {
        $student = Student::find($session->user_id);
        $enrollment = $student->enrollments()->latest()->first();
        
        $info = "";
        if($enrollment) {
            $info = $enrollment->classSection->name ?? '';
            if($enrollment->gradeLevel) $info = $enrollment->gradeLevel->name . " " . $info;
        }

        $msg = __('chatbot.main_menu', [
            'school' => $student->institution->name,
            'student' => $student->full_name,
            'class' => $info,
            'year' => $enrollment->academicSession->name ?? date('Y')
        ]);
        
        return $this->reply($session->phone_number, $msg, $session->institution_id);
    }

    protected function processStudentMenu($session, $text)
    {
        $student = Student::find($session->user_id);
        $text = strtolower(trim($text));
        
        if (in_array($text, ['logout', 'quitter', '0'])) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id);
        }

        switch ($text) {
            case '1': return $this->getHomework($session, $student);
            case '2': 
                $session->update(['status' => 'PAYMENT_METHOD_SELECT']); 
                return $this->reply($session->phone_number, __('chatbot.payment_method_menu'), $session->institution_id);
            case '3': return $this->getBalance($session, $student);
            case '4': return $this->getReportCard($session, $student);
            case '5': return $this->getMiscFees($session, $student);
            case '6': return $this->getActivities($session, $student);
            case '7': 
                $session->update(['status' => 'DEROGATION_DURATION_SELECT']); 
                return $this->reply($session->phone_number, __('chatbot.derogation_menu'), $session->institution_id);
            case '8': 
                $session->update(['status' => 'REQUEST_TYPE_SELECT']); 
                return $this->reply($session->phone_number, __('chatbot.request_menu'), $session->institution_id);
            case '9': 
                $session->update(['status' => 'QR_OTP_CONFIRM']); 
                return $this->reply($session->phone_number, __('chatbot.qr_verification'), $session->institution_id);
            default: 
                return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
        }
    }

    // --- STUDENT LOGIC IMPLEMENTATIONS ---

    protected function processPaymentMethod($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        if ($text == '1') {
            $link = "https://e-digitex.com/checkout?id=" . base64_encode($session->institution_id);
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.payment_link', ['link' => $link]), $session->institution_id);
        }
        if ($text == '2') {
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.mobile_money_instruction'), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    protected function getBalance($session, $student) {
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled'), $session->institution_id);
        
        $fees = FeeStructure::where('grade_level_id', $enrollment->grade_level_id)
            ->where('academic_session_id', $enrollment->academic_session_id)
            ->where('institution_id', $session->institution_id)
            ->where('payment_mode', 'global') 
            ->sum('amount');
            
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)->where('academic_session_id', $enrollment->academic_session_id))->sum('amount');
        
        $due = $fees - $paid;
        
        return $this->reply($session->phone_number, 
             __('chatbot.balance_info', [
                 'total' => number_format($fees, 2) . CurrencySymbol::default(),
                 'paid' => number_format($paid, 2) . CurrencySymbol::default(),
                 'due' => number_format($due, 2) . CurrencySymbol::default()
             ]), 
            $session->institution_id
        );
    }

    protected function getReportCard($session, $student) {
        $enrollment = $student->enrollments()->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled'), $session->institution_id);
        
        try {
            // Generate PDF
            $path = 'public/temp';
            if(!Storage::exists($path)) Storage::makeDirectory($path);
            
            $filename = 'Bulletin_' . $student->admission_number . '_' . time() . '.pdf';
            $pdf = Pdf::loadView('reports.bulletin_primary', [
                'student' => $student, 
                'enrollment' => $enrollment,
                'trimester' => 1,
                'data' => [] 
            ]);
            
            Storage::put($path . '/' . $filename, $pdf->output());
            $url = asset('storage/temp/' . $filename);
            
            // Check Accessibility for localhost testing
            if (request()->getHost() == '127.0.0.1' || request()->getHost() == 'localhost') {
                 // Infobip cannot fetch from localhost. Send text link instead.
                 return $this->reply($session->phone_number, __('chatbot.report_generated_local', ['url' => $url]), $session->institution_id);
            }

            // Use sendFile to send the actual PDF document
            $this->notificationService->performSendFile($session->phone_number, $url, __('chatbot.result_found'), $filename, $session->institution_id);
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error("Bot Report Error: " . $e->getMessage());
            return $this->reply($session->phone_number, __('chatbot.error_occurred'), $session->institution_id);
        }
    }

    protected function getHomework($session, $student)
    {
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled'), $session->institution_id);
        
        $hw = Assignment::where('class_section_id', $enrollment->class_section_id)
            ->where('deadline', '>=', now())
            ->latest()->take(3)->get();
            
        if($hw->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_homework'), $session->institution_id);
        
        $list = "";
        foreach($hw as $h) {
            $list .= "📚 " . $h->subject->name . ": " . $h->title . " (" . $h->deadline->format('d/m') . ")\n";
        }
        
        return $this->reply($session->phone_number, __('chatbot.homework_list', ['content' => $list]), $session->institution_id);
    }

    protected function getMiscFees($session, $student) {
        $fees = FeeStructure::where('institution_id', $session->institution_id)->where('frequency', 'one_time')->get();
        if($fees->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_fees_found'), $session->institution_id);
        
        $list = $fees->map(fn($f) => "- {$f->name}: {$f->amount} " . CurrencySymbol::default())->join("\n");
        return $this->reply($session->phone_number, __('chatbot.misc_fees_list', ['content' => $list]), $session->institution_id);
    }

    protected function getActivities($session, $student) {
        $events = Notice::where('institution_id', $session->institution_id)->latest()->take(5)->get();
        if($events->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_events_found'), $session->institution_id);
        
        $list = $events->map(fn($e) => "📅 {$e->title} (" . $e->created_at->format('d M') . ")")->join("\n");
        return $this->reply($session->phone_number, __('chatbot.activities_list', ['content' => $list]), $session->institution_id);
    }
    
    // QR Code Logic
    protected function processQrOtpConfirm($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        if ($text == '1') {
            $otp = rand(100000, 999999);
            $session->update(['otp' => $otp, 'status' => 'QR_OTP_INPUT']);
            
            $student = Student::find($session->user_id);
            $this->notificationService->sendOtpNotification($student, $otp);
            
            return $this->reply($session->phone_number, __('chatbot.otp_sent'), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    protected function processQrOtpInput($session, $text) {
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

            // Generate QR Image URL
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($token);
            
            // Fix: Send as IMAGE message via performSendImage
            $this->notificationService->performSendImage(
                $session->phone_number, 
                $qrUrl, 
                __('chatbot.qr_caption', ['student' => $student->first_name]), 
                $session->institution_id
            );
            
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            return $this->reply($session->phone_number, __('chatbot.qr_success_menu'), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_otp'), $session->institution_id);
    }
    
    // --- Request Flows ---
    protected function processDerogation($s, $t) { 
        if ($t == '0') { $s->update(['status'=>'ACTIVE']); return $this->sendStudentMenu($s); }
        $s->update(['status'=>'ACTIVE']); 
        // Mock ticket logic
        return $this->reply($s->phone_number, __('chatbot.derogation_submitted', ['days' => $t, 'ticket' => '#DGR'.rand(1000,9999)]), $s->institution_id); 
    }
    
    protected function processRequestType($s, $t) { 
        if ($t == '0') { $s->update(['status'=>'ACTIVE']); return $this->sendStudentMenu($s); }
        $s->update(['status'=>'REQUEST_REASON_SELECT']); 
        return $this->reply($s->phone_number, __('chatbot.request_reason_1'), $s->institution_id); 
    }
    
    protected function processRequestReason($s, $t) { 
        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, __('chatbot.request_submitted', ['type'=>'Req', 'reason'=>$t, 'ticket'=>'#REQ'.rand(1000,9999)]), $s->institution_id); 
    }

    // --- ADMIN MENU ---
    protected function sendAdminMenu($session) {
        $user = User::find($session->user_id);
        return $this->reply($session->phone_number, __('chatbot.admin_welcome', ['name' => $user->name]), $session->institution_id);
    }

    protected function processAdminMenu($session, $text) {
        if ($text == '0') { $session->delete(); return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id); }
        
        switch($text) {
            case '1': return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $this->getAdminStats($session->institution_id)), $session->institution_id);
            case '3': $session->update(['status' => 'ADMIN_RANKING_SELECT']); return $this->reply($session->phone_number, __('chatbot.admin_ranking_menu'), $session->institution_id);
            case '5': $session->update(['status' => 'ADMIN_EXPORT_SELECT']); return $this->reply($session->phone_number, __('chatbot.admin_export_menu'), $session->institution_id);
            default: return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
        }
    }
    
    protected function processAdminRanking($s, $t) { 
        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, "Ranking Data: 1. A (90%) 2. B (80%)", $s->institution_id); 
    }
    
    protected function processAdminExport($s, $t) { 
        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, __('chatbot.export_ready'), $s->institution_id); 
    }
    
    protected function processAdminSchoolExport($s, $t) { return $this->processAdminExport($s, $t); }

    // --- UTILS ---
    
    protected function getAdminStats($institutionId) {
        $totalPaid = Payment::where('institution_id', $institutionId)->sum('amount');
        $totalInvoiced = Invoice::where('institution_id', $institutionId)->sum('total_amount');
        
        return [
            'schools' => 1,
            'students' => Student::where('institution_id', $institutionId)->count(),
            'paid_students' => Invoice::where('institution_id', $institutionId)->where('status', 'paid')->distinct('student_id')->count(),
            'paid_percentage' => $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100) : 0,
            'amount_paid' => number_format($totalPaid, 2) . CurrencySymbol::default(),
            'outstanding' => number_format($totalInvoiced - $totalPaid, 2) . CurrencySymbol::default(),
            'total_balance' => number_format($totalInvoiced, 2) . CurrencySymbol::default()
        ];
    }

    protected function incrementAttempts($session, $msg)
    {
        $session->increment('attempts');
        if ($session->attempts >= 3) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.too_many_attempts'), $session->institution_id);
        }
        return $this->reply($session->phone_number, $msg . " (" . $session->attempts . "/3)", $session->institution_id);
    }

    protected function reply($to, $message, $institutionId)
    {
        $this->notificationService->performSend($to, $message, $institutionId, false, 'whatsapp');
        return response()->json(['status' => 'success']);
    }
}