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
use App\Models\StudentRequest;
use App\Models\Timetable;
use App\Models\ExamSchedule;
use App\Models\Campus;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\AcademicSession;
use App\Models\StudentAttendance;
use App\Models\Subscription;
use App\Models\GradeLevel;
use App\Enums\CurrencySymbol;
use App\Enums\RoleEnum;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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
                return $this->reply($phone, __('chatbot.session_ended') ?? "Session ended.", null);
            }

            // Route Logic
            if (!$session) {
                return $this->handleNewSession($phone, $text);
            }

            // Update timestamp & Apply Locale
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

                default:
                    // Self-heal: Reset to ACTIVE if state is unknown
                    $session->update(['status' => 'ACTIVE']); 
                    if ($session->user_type === 'student') return $this->sendStudentMenu($session);
                    return $this->sendStaffMenu($session);
            }

        } catch (\Throwable $e) {
            Log::error("Chatbot Critical Error: " . $e->getMessage());
            if (isset($data['from'])) {
                $p = preg_replace('/[^0-9]/', '', $data['from']);
                return $this->reply($p, __('chatbot.system_error') ?? "System error.", null);
            }
            return response()->json(['status' => 'error']);
        }
    }

    // --- 1. START ---
    protected function handleNewSession($phone, $text)
    {
        $textLower = strtolower($text);
        
        if (str_starts_with($textLower, 'admin')) {
            ChatSession::create([
                'phone_number' => $phone,
                'status' => 'AWAITING_ID',
                'user_type' => 'staff', 
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null,
                'locale' => 'fr' 
            ]);
            return $this->reply($phone, __('chatbot.admin_welcome_prompt'), null);
        }

        $keyword = ChatbotKeyword::where('keyword', $textLower)->first();
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
        if ($session->user_type === 'staff') {
            $user = User::where('username', $input)->orWhere('shortcode', $input)->first();
            
            if ($user) {
                $role = 'staff';
                if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) $role = 'super_admin';
                elseif ($user->hasRole(RoleEnum::HEAD_OFFICER->value)) $role = 'head_officer';
                elseif ($user->hasRole(RoleEnum::SCHOOL_ADMIN->value)) $role = 'school_admin';
                elseif ($user->hasRole(RoleEnum::TEACHER->value)) $role = 'teacher';

                $session->update(['user_type' => $role]);
                return $this->sendOtp($session, $user, 'staff');
            }
            return $this->incrementAttempts($session, __('chatbot.admin_id_invalid'));
        }

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
            'user_type' => $session->user_type, 
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
            
            if ($session->user_type === 'student') return $this->sendStudentMenu($session);
            return $this->sendStaffMenu($session);
        }
        return $this->incrementAttempts($session, __('chatbot.invalid_otp'));
    }

    // =================================================================================
    // --- 3. GLOBAL MENU HELPERS ---
    // =================================================================================

    /**
     * Appends the standard '0 for Main Menu' prompt ensuring users know how to return
     */
    protected function getReturnPrompt($session) {
        $msg = $session->locale === 'fr' ? "Retour au Menu" : "Return to Menu";
        return "\n\n👉 0️⃣ " . $msg;
    }

    // =================================================================================
    // --- 4. STAFF MENU ---
    // =================================================================================

    protected function getStaffMenuText($session) {
        $user = User::find($session->user_id);
        $role = $session->user_type; 
        
        $menu = "👤 *Bienvenue, " . $user->name . "*\n\n";

        if ($role === 'super_admin') {
            $menu .= "🛠 *Menu Super Admin*\n\n1️⃣ Finance Globale (Invoices)\n2️⃣ Subscriptions & Packages\n3️⃣ Ecoles (Actif/Inactif)\n4️⃣ SMS/WhatsApp Recharging\n";
        } 
        elseif ($role === 'head_officer' || $role === 'school_admin') {
            $menu .= "🏫 *Menu Administration*\n\n1️⃣ Global Dashboard\n2️⃣ School Aggregates\n3️⃣ Financial Ranking\n4️⃣ Stats & Forecast\n5️⃣ Export Report {Excel/PDF}\n";
        } 
        elseif ($role === 'teacher') {
            $menu .= "📚 *Menu Enseignant*\n\n1️⃣ Mon Horaire (Timetable)\n2️⃣ Mes Classes\n3️⃣ Présences (Aujourd'hui)\n";
        }

        $menu .= "\n9️⃣9️⃣ 🌐 Language / Langue\n🚪 Send *logout* to quit";
        return $menu;
    }

    protected function sendStaffMenu($session) {
        return $this->reply($session->phone_number, $this->getStaffMenuText($session), $session->institution_id);
    }

    protected function processStaffMenu($session, $text)
    {
        $text = strtolower(trim($text));

        // 1. Return to menu (Refresh)
        if ($text === '0' || $text === '00' || $text === 'menu') {
            return $this->sendStaffMenu($session);
        }

        // 2. Logout Action
        if (in_array($text, ['logout', 'quitter', 'exit'])) { 
            $session->delete(); 
            return $this->reply($session->phone_number, __('chatbot.logout_success') ?? "👋 Session closed.", $session->institution_id); 
        }

        // 3. Language Switcher
        if ($text === '99') {
            $newLocale = app()->getLocale() === 'en' ? 'fr' : 'en';
            $session->update(['locale' => $newLocale]);
            app()->setLocale($newLocale);
            $msg = $newLocale === 'fr' ? "✅ Langue changée en Français." : "✅ Language changed to English.";
            return $this->reply($session->phone_number, $msg . "\n\n" . $this->getStaffMenuText($session), $session->institution_id);
        }

        $role = $session->user_type;
        $user = User::find($session->user_id);

        if ($role === 'super_admin') {
            switch($text) {
                case '1': 
                    $invoiced = Subscription::sum('price_paid');
                    return $this->reply($session->phone_number, "💰 *Finance Globale*\nRevenus: " . number_format($invoiced, 2) . " " . CurrencySymbol::default() . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $active = Subscription::where('status', 'active')->where('end_date', '>=', now())->count();
                    $expired = Subscription::where('end_date', '<', now())->count();
                    return $this->reply($session->phone_number, "📦 *Subscriptions*\nActives: $active\nExpirées: $expired" . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $activeInst = Institution::where('is_active', true)->count();
                    $inactiveInst = Institution::where('is_active', false)->count();
                    return $this->reply($session->phone_number, "🏫 *Ecoles*\nActives: $activeInst\nInactives: $inactiveInst" . $this->getReturnPrompt($session), $session->institution_id);
                case '4': 
                    $sms = Institution::sum('sms_credits');
                    $wa = Institution::sum('whatsapp_credits');
                    return $this->reply($session->phone_number, "📲 *Crédits*\nSMS Alloués: $sms\nWhatsApp Alloués: $wa" . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
            }
        }
        
        elseif ($role === 'head_officer' || $role === 'school_admin') {
            switch($text) {
                case '1': 
                    return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $this->getAdminStats($session->institution_id)) . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $data = $this->getSchoolStats($session->institution_id); 
                    return $this->reply($session->phone_number, "🏫 *School Aggregates (Campus)*\n\n" . $data . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $session->update(['status' => 'ADMIN_RANKING_SELECT']); 
                    return $this->reply($session->phone_number, __('chatbot.admin_ranking_menu'), $session->institution_id);
                case '4': 
                    $currentSess = AcademicSession::where('institution_id', $session->institution_id)->where('is_current', true)->first();
                    $totalRevenue = Payment::where('institution_id', $session->institution_id)->sum('amount');
                    $monthlyRevenue = Payment::where('institution_id', $session->institution_id)->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount');
                    return $this->reply($session->phone_number, "📈 *Stats & Forecast*\nSession Active: " . ($currentSess->name ?? 'N/A') . "\nRevenu Total: " . number_format($totalRevenue, 2) . " " . CurrencySymbol::default() . "\nRevenu du Mois: " . number_format($monthlyRevenue, 2) . " " . CurrencySymbol::default() . $this->getReturnPrompt($session), $session->institution_id);
                case '5': 
                    $session->update(['status' => 'ADMIN_EXPORT_SELECT']); 
                    return $this->reply($session->phone_number, __('chatbot.admin_export_menu'), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
            }
        }
        
        elseif ($role === 'teacher') {
            $staffId = $user->staff->id ?? 0;
            switch($text) {
                case '1': 
                    $today = strtolower(now()->format('l'));
                    $tt = Timetable::with(['subject', 'classSection'])->where('teacher_id', $staffId)->where('day_of_week', $today)->orderBy('start_time')->get();
                    $msg = "📅 *Horaire d'aujourd'hui (" . ucfirst($today) . ")*\n\n";
                    $msg .= $tt->isEmpty() ? "Aucun cours prévu." : $tt->map(fn($t) => "🕒 {$t->start_time->format('H:i')} - {$t->end_time->format('H:i')}\n📖 {$t->subject->name} ({$t->classSection->name})")->join("\n\n");
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $classIds = ClassSubject::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
                    $classIds2 = Timetable::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
                    $classes = ClassSection::with('gradeLevel')->whereIn('id', array_unique(array_merge($classIds, $classIds2)))->get();
                    $msg = "🏫 *Mes Classes Assignées*\n\n";
                    $msg .= $classes->isEmpty() ? "Aucune classe assignée." : $classes->map(fn($c) => "👉 " . ($c->gradeLevel->name ?? '') . " - " . $c->name)->join("\n");
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $attendances = StudentAttendance::where('marked_by', $user->id)->whereDate('attendance_date', today())->count();
                    return $this->reply($session->phone_number, "✅ *Présences du Jour*\nVous avez marqué les présences pour {$attendances} étudiant(s) aujourd'hui." . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
            }
        }

        return $this->reply($session->phone_number, __('chatbot.unknown_command'), $session->institution_id);
    }

    // =================================================================================
    // --- 5. STUDENT MENU ---
    // =================================================================================

    protected function getMenuText($session) {
        $student = Student::find($session->user_id);
        if (!$student) return "Error";
        
        $enrollment = $student->enrollments()->latest()->first();
        $info = "";
        if($enrollment) {
            $info = $enrollment->classSection->name ?? '';
            if($enrollment->gradeLevel) $info = $enrollment->gradeLevel->name . " " . $info;
        }

        $baseMenu = __('chatbot.main_menu', [
            'school' => $student->institution->name,
            'student' => $student->full_name,
            'class' => $info,
            'year' => $enrollment->academicSession->name ?? date('Y')
        ]);

        return $baseMenu . "\n\n9️⃣9️⃣ 🌐 Language / Langue\n🚪 Send *logout* to quit";
    }

    protected function sendStudentMenu($session)
    {
        return $this->reply($session->phone_number, $this->getMenuText($session), $session->institution_id);
    }

    protected function processStudentMenu($session, $text)
    {
        $student = Student::find($session->user_id);
        $text = strtolower(trim($text));
        
        // 1. Return to menu (Refresh)
        if ($text === '0' || $text === 'menu') {
            return $this->sendStudentMenu($session);
        }

        // 2. Logout Action
        if (in_array($text, ['logout', 'quitter', 'exit'])) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.logout_success') ?? "👋 Session closed.", $session->institution_id);
        }

        // 3. Language Switcher
        if ($text === '99') {
            $newLocale = app()->getLocale() === 'en' ? 'fr' : 'en';
            $session->update(['locale' => $newLocale]);
            app()->setLocale($newLocale);
            $msg = $newLocale === 'fr' ? "✅ Langue changée en Français." : "✅ Language changed to English.";
            return $this->reply($session->phone_number, $msg . "\n\n" . $this->getMenuText($session), $session->institution_id);
        }

        switch ($text) {
            case '1': return $this->getHomework($session, $student);
            case '2': 
                $session->update(['status' => 'PAYMENT_METHOD_SELECT']); 
                return $this->processPaymentMenu($session, $student); 
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
                return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
        }
    }

    // --- STUDENT LOGIC IMPLEMENTATIONS ---
    
    protected function processPaymentMenu($session, $student) {
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $session->institution_id);
        
        $fees = FeeStructure::where('grade_level_id', $enrollment->grade_level_id)
            ->where('academic_session_id', $enrollment->academic_session_id)
            ->where('institution_id', $session->institution_id)
            ->where('payment_mode', 'global')->sum('amount');
            
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)->where('academic_session_id', $enrollment->academic_session_id))->sum('amount');
        $due = $fees - $paid;
        
        return $this->reply($session->phone_number, __('chatbot.payment_method_menu', ['due' => number_format($due, 2) . CurrencySymbol::default(), 'total' => number_format($fees, 2) . CurrencySymbol::default()]), $session->institution_id);
    }

    protected function processPaymentMethod($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        $institutionId = $session->institution_id;
        $studentId = $session->user_id;

        $unpaidInvoices = Invoice::where('student_id', $studentId)->whereIn('status', ['unpaid', 'partial', 'overdue'])->get();
            
        if ($unpaidInvoices->isEmpty()) {
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, "Vous n'avez aucune facture en attente de paiement." . $this->getReturnPrompt($session), $institutionId);
        }

        $totalDue = $unpaidInvoices->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);

        if ($text == '1') {
            $token = base64_encode('checkout-' . $studentId . '-' . time());
            $link = route('login') . "?ref=" . $token; 
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.payment_link', ['link' => $link]) . "\nTotal à payer: " . number_format($totalDue, 2) . " " . CurrencySymbol::default() . $this->getReturnPrompt($session), $institutionId);
        }
        if ($text == '2') {
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.mobile_money_instruction') . "\nMontant Dû: " . number_format($totalDue, 2) . " " . CurrencySymbol::default() . $this->getReturnPrompt($session), $institutionId);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $institutionId);
    }

    protected function getBalance($session, $student) {
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $session->institution_id);
        
        $fees = FeeStructure::where('grade_level_id', $enrollment->grade_level_id)->where('academic_session_id', $enrollment->academic_session_id)->where('institution_id', $session->institution_id)->where('payment_mode', 'global')->sum('amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)->where('academic_session_id', $enrollment->academic_session_id))->sum('amount');
        $due = $fees - $paid;
        
        return $this->reply($session->phone_number, __('chatbot.balance_info', [
                 'total' => number_format($fees, 2) . CurrencySymbol::default(),
                 'paid' => number_format($paid, 2) . CurrencySymbol::default(),
                 'due' => number_format($due, 2) . CurrencySymbol::default()
        ]) . $this->getReturnPrompt($session), $session->institution_id);
    }

    // SYMMETRICAL REPORT CARD LOGIC WITH DASHBOARD SETTINGS
    protected function getReportCard($session, $student) {
        $institutionId = $session->institution_id;

        // 1. FINANCIAL RESTRICTION POLICY CHECK (Symmetry with Dashboard)
        $isBlocked = InstitutionSetting::where('institution_id', $institutionId)->where('key', 'block_reports_on_debt')->value('value');

        if ($isBlocked == '1') {
            $unpaid = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total_amount - paid_amount'));

            if ($unpaid > 0) {
                $currency = CurrencySymbol::default();
                $formattedDebt = number_format($unpaid, 2) . ' ' . $currency;
                
                $fallbackFr = "⛔ Accès refusé. Vous avez un solde impayé de $formattedDebt. Veuillez régler pour voir vos résultats.";
                $fallbackEn = "⛔ Access denied. You have an outstanding balance of $formattedDebt. Please settle to view results.";
                $msg = __('chatbot.financial_restriction_msg', ['amount' => $formattedDebt]);
                if ($msg === 'chatbot.financial_restriction_msg') $msg = $session->locale === 'fr' ? $fallbackFr : $fallbackEn;

                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
            }
        }

        // 2. CHECK ENROLLMENT & SESSION
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $institutionId);

        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if (!$currentSession) return $this->reply($session->phone_number, __('chatbot.no_session') . $this->getReturnPrompt($session), $institutionId);

        // 3. CHECK EMPTY BULLETIN (No Marks Validation)
        $hasMarks = ExamRecord::where('student_id', $student->id)
            ->whereHas('exam', fn($q) => $q->where('academic_session_id', $currentSession->id))->exists();

        if (!$hasMarks) {
            $msg = __('chatbot.no_results_found');
            if ($msg === 'chatbot.no_results_found') {
                $msg = $session->locale === 'fr' ? "📭 Aucun résultat n'est encore disponible pour cette session." : "📭 No exam results are available for this session yet.";
            }
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
        }

        // 4. GENERATE SECURE SIGNED ROUTE (Identical to Dashboard)
        $downloadUrl = URL::signedRoute('reports.bulletin', [
            'student_id' => $student->id,
            'mode' => 'single',
            'report_scope' => 'trimester',
            'trimester' => 1
        ], now()->addMinutes(30));

        $filename = "Bulletin_{$student->admission_number}.pdf";

        // 5. SEND FILE
        if (request()->getHost() == '127.0.0.1' || request()->getHost() == 'localhost') {
            return $this->reply($session->phone_number, "📄 " . __('chatbot.report_generated_local', ['url' => $downloadUrl]) . $this->getReturnPrompt($session), $institutionId);
        }

        $caption = __('chatbot.result_found') . $this->getReturnPrompt($session);
        $this->notificationService->performSendFile($session->phone_number, $downloadUrl, $caption, $filename, $institutionId);
        return response()->json(['status' => 'success']);
    }

    protected function getHomework($session, $student)
    {
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $session->institution_id);
        
        $hw = Assignment::where('class_section_id', $enrollment->class_section_id)->where('deadline', '>=', now())->latest()->take(3)->get();
        if($hw->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_homework') . $this->getReturnPrompt($session), $session->institution_id);
        
        $list = "";
        foreach($hw as $h) $list .= "📚 " . $h->subject->name . ": " . $h->title . " (" . $h->deadline->format('d/m') . ")\n";
        
        return $this->reply($session->phone_number, __('chatbot.homework_list', ['content' => $list]) . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getMiscFees($session, $student) {
        $fees = FeeStructure::where('institution_id', $session->institution_id)->where('frequency', 'one_time')->get();
        if($fees->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_fees_found') . $this->getReturnPrompt($session), $session->institution_id);
        
        $list = $fees->map(fn($f) => "- {$f->name}: {$f->amount} " . CurrencySymbol::default())->join("\n");
        return $this->reply($session->phone_number, __('chatbot.misc_fees_list', ['content' => $list]) . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getActivities($session, $student) {
        $events = Notice::where('institution_id', $session->institution_id)->latest()->take(5)->get();
        if($events->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_events_found') . $this->getReturnPrompt($session), $session->institution_id);
        
        $list = $events->map(fn($e) => "📅 {$e->title} (" . $e->created_at->format('d M') . ")")->join("\n");
        return $this->reply($session->phone_number, __('chatbot.activities_list', ['content' => $list]) . $this->getReturnPrompt($session), $session->institution_id);
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
        return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . __('chatbot.qr_verification'), $session->institution_id);
    }

    protected function processQrOtpInput($session, $text) {
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

            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($token);
            
            $this->notificationService->performSendImage(
                $session->phone_number, 
                $qrUrl, 
                __('chatbot.qr_caption', ['student' => $student->first_name]) . $this->getReturnPrompt($session), 
                $session->institution_id
            );
            
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            return response()->json(['status' => 'success']);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_otp') . $this->getReturnPrompt($session), $session->institution_id);
    }
    
    protected function processDerogation($s, $t) { 
        if ($t == '0') { $s->update(['status'=>'ACTIVE']); return $this->sendStudentMenu($s); }
        
        $student = Student::find($s->user_id);
        $ticket = 'DGR-' . strtoupper(Str::random(5));
        
        if($student) {
             $enrollment = $student->enrollments()->latest()->first();
             StudentRequest::create([
                'institution_id' => $s->institution_id,
                'student_id' => $student->id,
                'academic_session_id' => $enrollment->academic_session_id ?? 0,
                'type' => 'leave',
                'reason' => "Demande de dérogation parentale pour {$t} jours (Soumis via Chatbot).",
                'start_date' => now(),
                'end_date' => now()->addDays((int)$t),
                'status' => 'pending',
                'ticket_number' => $ticket,
                'created_by' => $s->user_id 
             ]);
        }

        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, __('chatbot.derogation_submitted', ['days' => $t, 'ticket' => $ticket]) . $this->getReturnPrompt($s), $s->institution_id); 
    }
    
     protected function processRequestType($session, $text) 
    {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        $validTypes = ['1' => 'absence', '2' => 'late', '3' => 'sick', '4' => 'early_exit', '5' => 'other'];
        
        if (!isset($validTypes[$text])) return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . __('chatbot.request_menu'), $session->institution_id);
        
        $session->update([
            'status' => 'REQUEST_REASON_SELECT',
            'identifier_input' => "REQ_TYPE:" . $validTypes[$text]
        ]);
        
        return $this->reply($session->phone_number, __('requests.chatbot_ask_reason'), $session->institution_id);
    }
    
    protected function processRequestReason($session, $text) 
    {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }

        $type = explode(':', $session->identifier_input)[1] ?? 'other';
        $student = Student::find($session->user_id);
        $ticket = null;

        if($student) {
             $enrollment = $student->enrollments()->latest()->first();
             if($enrollment) {
                 $ticket = 'REQ-' . strtoupper(Str::random(8));
                 StudentRequest::create([
                    'institution_id' => $session->institution_id,
                    'student_id' => $session->user_id,
                    'academic_session_id' => $enrollment->academic_session_id,
                    'type' => $type,
                    'reason' => $text,
                    'start_date' => now(), 
                    'status' => 'pending',
                    'ticket_number' => $ticket,
                    'created_by' => $session->user_id
                 ]);
             }
        }

        $session->update(['status' => 'ACTIVE']); 
        return $this->reply($session->phone_number, __('requests.chatbot_submitted', ['ticket' => $ticket ?? 'N/A']) . $this->getReturnPrompt($session), $session->institution_id); 
    }

    // --- AUTHENTIC ADMIN UTILS ---
    
    protected function processAdminRanking($s, $t) { 
        if ($t == '0' || $t == '00') { $s->update(['status'=>'ACTIVE']); return $this->sendStaffMenu($s); }
        
        $institutionId = $s->institution_id;
        $classes = ClassSection::where('institution_id', $institutionId)->get();
        $ranking = [];
        
        foreach($classes as $class) {
            $studentIds = StudentEnrollment::where('class_section_id', $class->id)->where('status', 'active')->pluck('student_id');
            if($studentIds->isEmpty()) continue;
            
            $totalPaid = Payment::where('institution_id', $institutionId)->whereHas('invoice', fn($q) => $q->whereIn('student_id', $studentIds))->sum('amount');
            $ranking[] = ['name' => $class->name, 'paid' => $totalPaid];
        }
        
        usort($ranking, fn($a, $b) => $b['paid'] <=> $a['paid']);
        
        $msg = "🏆 *Classement des Classes (Paiements)*\n";
        foreach(array_slice($ranking, 0, 10) as $idx => $r) $msg .= ($idx + 1) . ". {$r['name']} - " . number_format($r['paid'], 2) . " " . CurrencySymbol::default() . "\n";
        if(empty($ranking)) $msg .= "Aucune donnée de paiement disponible.";
        
        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $institutionId); 
    }
    
    protected function processAdminExport($s, $t) { 
        if ($t == '0' || $t == '00') { $s->update(['status'=>'ACTIVE']); return $this->sendStaffMenu($s); }
        
        $institutionId = $s->institution_id;
        
        try {
            $stats = $this->getAdminStats($institutionId);
            $pdf = Pdf::loadView('reports.admin_summary', compact('stats'));
            
            $dir = 'temp';
            if(!Storage::disk('public')->exists($dir)) Storage::disk('public')->makeDirectory($dir);
            
            $fileName = 'Export_' . time() . '.pdf';
            $path = $dir . '/' . $fileName;
            Storage::disk('public')->put($path, $pdf->output());
            $url = url('storage/' . $path); 
            
            $this->notificationService->performSend($s->phone_number, __('chatbot.export_ready'), $institutionId, false, 'whatsapp');
            $this->notificationService->performSendFile($s->phone_number, $url, 'Export.pdf', 'Export.pdf', $institutionId);

        } catch (\Exception $e) {
            Log::error("Chatbot Export Failed: " . $e->getMessage());
            $this->notificationService->performSend($s->phone_number, "❌ Erreur lors de la génération de l'export.", $institutionId, false, 'whatsapp');
        }

        $s->update(['status'=>'ACTIVE']); 
        return $this->sendStaffMenu($s); 
    }

    protected function getAdminStats($institutionId) {
        $totalPaid = Payment::where('institution_id', $institutionId)->sum('amount');
        $totalInvoiced = Invoice::where('institution_id', $institutionId)->sum('total_amount');
        
        return [
            'schools' => Campus::where('institution_id', $institutionId)->count(),
            'students' => Student::where('institution_id', $institutionId)->count(),
            'paid_students' => Invoice::where('institution_id', $institutionId)->where('status', 'paid')->distinct('student_id')->count(),
            'paid_percentage' => $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100) : 0,
            'amount_paid' => number_format($totalPaid, 2) . CurrencySymbol::default(),
            'outstanding' => number_format($totalInvoiced - $totalPaid, 2) . CurrencySymbol::default(),
            'total_balance' => number_format($totalInvoiced, 2) . CurrencySymbol::default()
        ];
    }
    
    protected function getSchoolStats($institutionId) {
        $campuses = Campus::where('institution_id', $institutionId)->get();
        if ($campuses->isEmpty()) return "Aucun campus configuré.";
        
        return $campuses->map(function($c) {
            $students = Student::where('campus_id', $c->id)->count();
            $staff = \App\Models\Staff::where('campus_id', $c->id)->count();
            return "🏫 {$c->name}: {$students} Étudiants | {$staff} Agents";
        })->join("\n");
    }

    protected function incrementAttempts($session, $msg)
    {
        $session->increment('attempts');
        if ($session->attempts >= 3) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.too_many_attempts') ?? "Too many attempts. Session deleted.", $session->institution_id);
        }
        return $this->reply($session->phone_number, $msg . " (" . $session->attempts . "/3)", $session->institution_id);
    }

    protected function reply($to, $message, $institutionId)
    {
        $this->notificationService->performSend($to, $message, $institutionId, false, 'whatsapp');
        return response()->json(['status' => 'success']);
    }
}