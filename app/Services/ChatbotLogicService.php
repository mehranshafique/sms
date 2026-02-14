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
                    // Route based on Role stored in session
                    if ($session->user_type === 'student') {
                        return $this->processStudentMenu($session, $text);
                    }
                    // For Staff/Admins, route to specific menu processor
                    return $this->processStaffMenu($session, $text);
                    
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
                    // Self-heal: Reset to ACTIVE if state is unknown
                    $session->update(['status' => 'ACTIVE']); 
                    // Determine which menu to show based on user type
                    if ($session->user_type === 'student') {
                        return $this->sendStudentMenu($session);
                    } else {
                        return $this->sendStaffMenu($session);
                    }
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
        $textLower = strtolower($text);
        
        // Admin/Staff Flow
        if (str_starts_with($textLower, 'admin')) {
            ChatSession::create([
                'phone_number' => $phone,
                'status' => 'AWAITING_ID',
                'user_type' => 'staff', // Generic type for now
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null,
                'locale' => 'fr' // Default or detect
            ]);
            return $this->reply($phone, __('chatbot.admin_welcome_prompt'), null);
        }

        // Student Flow
        $keyword = ChatbotKeyword::where('keyword', $textLower)->first();
        // Check DB or common defaults
        if ($keyword || in_array($textLower, ['bonjour', 'hello', 'digitex', 'menu', 'start', 'salut', 'hi'])) {
            $locale = $keyword->language ?? (in_array($textLower, ['hello', 'hi', 'start']) ? 'en' : 'fr'); 
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
        // Admin/Staff Auth
        if ($session->user_type === 'staff') {
            $user = User::where('username', $input)->orWhere('shortcode', $input)->first();
            
            if ($user) {
                // Determine specific role for session context
                $role = 'staff';
                if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) $role = 'super_admin';
                elseif ($user->hasRole(RoleEnum::HEAD_OFFICER->value)) $role = 'head_officer';
                elseif ($user->hasRole(RoleEnum::SCHOOL_ADMIN->value)) $role = 'school_admin';
                elseif ($user->hasRole(RoleEnum::TEACHER->value)) $role = 'teacher';

                // Update session type to specific role for menu logic
                $session->update(['user_type' => $role]);
                
                return $this->sendOtp($session, $user, 'staff');
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
            'user_type' => $session->user_type, // Persist the specific role type
            'institution_id' => $model->institution_id ?? $model->institute_id,
            'otp' => $otp,
            'status' => 'AWAITING_OTP',
            'identifier_input' => $type 
        ]);

        $this->notificationService->performSend($cleanPhone, __('chatbot.otp_sms_message', ['otp' => $otp]), $session->institution_id, true, 'sms');
        
        $masked = Str::mask($cleanPhone, '*', 3, -3);
        return $this->reply($session->phone_number, __('chatbot.otp_sent_notification', ['phone' => $masked]), $session->institution_id);
    }

    protected function processOtp($session, $input)
    {
        if (trim($input) == $session->otp) {
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            
            // Route to correct menu based on Role
            if ($session->user_type === 'student') {
                return $this->sendStudentMenu($session);
            } else {
                return $this->sendStaffMenu($session);
            }
        }
        return $this->incrementAttempts($session, __('chatbot.invalid_otp'));
    }

    // =================================================================================
    // --- 3. MENU DISPATCHER (By Role) ---
    // =================================================================================

    protected function getStaffMenuText($session) {
        $user = User::find($session->user_id);
        $role = $session->user_type; // super_admin, head_officer, school_admin, teacher
        
        $menu = "👤 *Bienvenue, " . $user->name . "*\n\n";

        if ($role === 'super_admin') {
            $menu .= "🛠 *Menu Super Admin*\n\n" .
                     "1️⃣ Finance Globale (Invoices)\n" .
                     "2️⃣ Subscriptions & Packages\n" .
                     "3️⃣ Ecoles (Actif/Inactif)\n" .
                     "4️⃣ SMS/WhatsApp Recharging\n" .
                     "5️⃣ Configuration Système\n";
        } 
        elseif ($role === 'head_officer' || $role === 'school_admin') {
            $menu .= "🏫 *Menu Administration*\n\n" .
                     "1️⃣ Global Dashboard\n" .
                     "2️⃣ School Aggregates\n" .
                     "3️⃣ Financial Ranking\n" .
                     "4️⃣ Stats & Forecast\n" .
                     "5️⃣ Export Report {Excel/PDF}\n" .
                     "6️⃣ Aide\n";
        } 
        elseif ($role === 'teacher') {
            $menu .= "📚 *Menu Enseignant*\n\n" .
                     "1️⃣ Mon Horaire (Timetable)\n" .
                     "2️⃣ Mes Classes\n" .
                     "3️⃣ Présences (Aujourd'hui)\n" .
                     "4️⃣ Devoirs (Assignments)\n" .
                     "5️⃣ Examens & Notes\n";
        }

        $menu .= "\n0️⃣ Quitter";
        return $menu;
    }

    protected function sendStaffMenu($session) {
        return $this->reply($session->phone_number, $this->getStaffMenuText($session), $session->institution_id);
    }

    protected function processStaffMenu($session, $text)
    {
        if ($text == '0') { 
            $session->delete(); 
            return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id); 
        }

        // Logic to Repeat Menu on invalid or '00'
        if ($text == '00') {
             return $this->sendStaffMenu($session);
        }

        $role = $session->user_type;

        // --- SUPER ADMIN LOGIC ---
        if ($role === 'super_admin') {
            switch($text) {
                case '1': return $this->reply($session->phone_number, "💰 *Finance Globale*\nTotal Facturé: 500k\nReçu: 300k", $session->institution_id);
                case '2': return $this->reply($session->phone_number, "📦 *Subscriptions*\nActifs: 10\nExpirés: 2", $session->institution_id);
                case '3': return $this->reply($session->phone_number, "🏫 *Ecoles*\nActives: 15\nInactives: 3", $session->institution_id);
                case '4': return $this->reply($session->phone_number, "📲 *SMS/WhatsApp*\nSolde SMS: 5000\nSolde WA: 2000\nPour recharger, contactez le support.", $session->institution_id);
                // Invalid Fallback
                default: return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getStaffMenuText($session), $session->institution_id);
            }
        }
        
        // --- SCHOOL ADMIN / HEAD OFFICER LOGIC ---
        elseif ($role === 'head_officer' || $role === 'school_admin') {
            switch($text) {
                case '1': return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $this->getAdminStats($session->institution_id)), $session->institution_id);
                case '2': 
                    $data = $this->getSchoolStats(); 
                    return $this->reply($session->phone_number, "🏫 *School Aggregates*\n" . $data, $session->institution_id);
                case '3': 
                    $session->update(['status' => 'ADMIN_RANKING_SELECT']); 
                    return $this->reply($session->phone_number, __('chatbot.admin_ranking_menu'), $session->institution_id);
                case '4': return $this->reply($session->phone_number, "📈 *Stats & Forecast*\nCroissance: +5%\nProjection: 100k", $session->institution_id);
                case '5': 
                    $session->update(['status' => 'ADMIN_EXPORT_SELECT']); 
                    return $this->reply($session->phone_number, __('chatbot.admin_export_menu'), $session->institution_id);
                case '6': return $this->reply($session->phone_number, __('chatbot.admin_help'), $session->institution_id);
                default: return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getStaffMenuText($session), $session->institution_id);
            }
        }
        
        // --- TEACHER LOGIC ---
        elseif ($role === 'teacher') {
            switch($text) {
                case '1': return $this->reply($session->phone_number, "📅 *Horaire*\n08:00 - Math (1A)\n10:00 - Physique (2B)", $session->institution_id);
                case '2': return $this->reply($session->phone_number, "🏫 *Classes*\n1A, 2B, 3C", $session->institution_id);
                case '3': return $this->reply($session->phone_number, "✅ *Présences*\nMarquer les présences via le portail web.", $session->institution_id);
                default: return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getStaffMenuText($session), $session->institution_id);
            }
        }

        return $this->reply($session->phone_number, __('chatbot.unknown_command'), $session->institution_id);
    }

    // --- STUDENT MENU (UNCHANGED) ---
    protected function getMenuText($session) {
        $student = Student::find($session->user_id);
        if (!$student) return "Error";
        
        $enrollment = $student->enrollments()->latest()->first();
        $info = "";
        if($enrollment) {
            $info = $enrollment->classSection->name ?? '';
            if($enrollment->gradeLevel) $info = $enrollment->gradeLevel->name . " " . $info;
        }

        return __('chatbot.main_menu', [
            'school' => $student->institution->name,
            'student' => $student->full_name,
            'class' => $info,
            'year' => $enrollment->academicSession->name ?? date('Y')
        ]);
    }

    protected function sendStudentMenu($session)
    {
        return $this->reply($session->phone_number, $this->getMenuText($session), $session->institution_id);
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
                // Fix: Invalid Option -> Show Error AND Menu
                return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getMenuText($session), $session->institution_id);
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
            
            if (request()->getHost() == '127.0.0.1' || request()->getHost() == 'localhost') {
                 return $this->reply($session->phone_number, __('chatbot.report_generated_local', ['url' => $url]), $session->institution_id);
            }

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
        // Invalid -> Repeat Options
        return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . __('chatbot.qr_verification'), $session->institution_id);
    }

    protected function processQrOtpInput($session, $text) {
        // Fix: Allow 0 to cancel even in OTP input state
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
    
    protected function processDerogation($s, $t) { 
        if ($t == '0') { $s->update(['status'=>'ACTIVE']); return $this->sendStudentMenu($s); }
        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, __('chatbot.derogation_submitted', ['days' => $t, 'ticket' => '#123']), $s->institution_id); 
    }
    
    protected function processRequestType($s, $t) { 
        if ($t == '0') { $s->update(['status'=>'ACTIVE']); return $this->sendStudentMenu($s); }
        $s->update(['status'=>'REQUEST_REASON_SELECT']); 
        return $this->reply($s->phone_number, __('chatbot.request_reason_1'), $s->institution_id); 
    }
    
    protected function processRequestReason($s, $t) { 
        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, __('chatbot.request_submitted', ['type'=>'Req', 'reason'=>$t, 'ticket'=>'#456']), $s->institution_id); 
    }

    // --- ADMIN MENU ---
    protected function getAdminMenuText($user) {
        return __('chatbot.admin_welcome', ['name' => $user->name]);
    }

    protected function sendAdminMenu($session) {
        $user = User::find($session->user_id);
        return $this->reply($session->phone_number, $this->getAdminMenuText($user), $session->institution_id);
    }

    protected function processAdminMenu($session, $text) {
        if ($text == '0') { $session->delete(); return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id); }
        
        // Fix: '00' Logic to Return to Menu (Not Logout)
        if ($text == '00') { 
            return $this->sendAdminMenu($session);
        }

        switch($text) {
            case '1': return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $this->getAdminStats($session->institution_id)), $session->institution_id);
            case '3': $session->update(['status' => 'ADMIN_RANKING_SELECT']); return $this->reply($session->phone_number, __('chatbot.admin_ranking_menu'), $session->institution_id);
            case '5': $session->update(['status' => 'ADMIN_EXPORT_SELECT']); return $this->reply($session->phone_number, __('chatbot.admin_export_menu'), $session->institution_id);
            default: return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getAdminMenuText(User::find($session->user_id)), $session->institution_id);
        }
    }
    
    protected function processAdminRanking($s, $t) { 
        // 00 to Back
        if ($t == '00') { $s->update(['status'=>'ACTIVE']); return $this->sendAdminMenu($s); }
        
        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, "Ranking Data Mock", $s->institution_id); 
    }
    
    protected function processAdminExport($s, $t) { 
        if ($t == '00') { $s->update(['status'=>'ACTIVE']); return $this->sendAdminMenu($s); }
        
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
    
    protected function getSchoolStats() {
        return Institution::take(3)->get()->map(fn($i) => "🏫 {$i->name}: " . $i->students()->count() . " Students")->join("\n");
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