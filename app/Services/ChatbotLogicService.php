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
use App\Models\ClassSection;
use App\Models\AcademicSession;
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
            $phone = preg_replace('/[^0-9]/', '', $phone); 

            $session = ChatSession::where('phone_number', $phone)->first();

            // Timeout Check (30 mins)
            if ($session && now()->gt($session->expires_at)) {
                $session->delete();
                return $this->reply($phone, __('chatbot.session_ended'), null);
            }

            if (!$session) {
                return $this->handleNewSession($phone, $text);
            }

            $session->update(['last_interaction_at' => now(), 'expires_at' => now()->addMinutes(30)]);
            if ($session->locale) app()->setLocale($session->locale);

            switch ($session->status) {
                case 'AWAITING_ID': return $this->processIdentity($session, $text);
                case 'AWAITING_OTP': return $this->processOtp($session, $text);
                
                case 'ACTIVE':
                    if ($session->user_type === 'student') return $this->processStudentMenu($session, $text);
                    return $this->processStaffMenu($session, $text);
                    
                // Common Flows
                case 'PAYMENT_METHOD_SELECT': return $this->processPaymentMethod($session, $text);
                case 'DEROGATION_DURATION_SELECT': return $this->processDerogation($session, $text);
                case 'QR_OTP_CONFIRM': return $this->processQrOtpConfirm($session, $text);
                case 'QR_OTP_INPUT': return $this->processQrOtpInput($session, $text);
                
                // Request Flows
                case 'REQUEST_STUDENT_SEARCH': return $this->processRequestStudentSearch($session, $text);
                case 'REQUEST_TYPE_SELECT': return $this->processRequestType($session, $text);
                case 'REQUEST_REASON_SELECT': return $this->processRequestReason($session, $text);
                
                // Admin Flows
                case 'ADMIN_RANKING_SELECT': return $this->processAdminRanking($session, $text);
                case 'ADMIN_EXPORT_SELECT': return $this->processAdminExport($session, $text);
                case 'ADMIN_SCHOOL_SELECT': return $this->processAdminSchoolExport($session, $text);

                default:
                    // Self-heal
                    $session->update(['status' => 'ACTIVE']); 
                    $menu = ($session->user_type === 'student') ? $this->getMenuText($session) : $this->getStaffMenuText($session);
                    return $this->reply($phone, __('chatbot.unknown_state_error') . "\n\n" . $menu, $session->institution_id);
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

    // --- 1. START & AUTH ---
    protected function handleNewSession($phone, $text)
    {
        $textLower = strtolower($text);
        
        // Admin Flow
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

        // Student Flow
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

    protected function processIdentity($session, $input)
    {
        if ($session->user_type === 'staff') {
            $user = User::where('username', $input)->orWhere('shortcode', $input)->first();
            if ($user && ($user->hasRole([RoleEnum::HEAD_OFFICER->value, RoleEnum::SUPER_ADMIN->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::TEACHER->value]))) {
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
            'user_type' => ($type == 'student' ? 'student' : 'staff'),
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
            
            if ($session->user_type === 'student') {
                return $this->sendStudentMenu($session);
            } else {
                return $this->sendStaffMenu($session);
            }
        }
        return $this->incrementAttempts($session, __('chatbot.invalid_otp'));
    }

    // --- 2. STUDENT MENU ---
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

    protected function sendStudentMenu($session) {
        return $this->reply($session->phone_number, $this->getMenuText($session), $session->institution_id);
    }

    protected function processStudentMenu($session, $text) { 
        if ($text == '00' || $text == '0') { 
             $session->delete();
             return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id);
        }

        $student = Student::find($session->user_id);

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
                // Student can only create request for themselves (SELF)
                $session->update(['status' => 'REQUEST_TYPE_SELECT', 'identifier_input' => 'SELF']); 
                return $this->reply($session->phone_number, __('chatbot.request_menu'), $session->institution_id);
            case '9': 
                $session->update(['status' => 'QR_OTP_CONFIRM']); 
                return $this->reply($session->phone_number, __('chatbot.qr_verification'), $session->institution_id);
            default: 
                return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        }
    }

    // --- 3. STAFF MENU ---
    protected function getStaffMenuText($session) {
        $user = User::find($session->user_id);
        
        $menu = "👤 *Welcome, " . $user->name . "*\n\n";
        $menu .= "Please choose an option:\n\n";

        // Admin Options (Head Officer / School Admin / Super Admin)
        if ($user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
            $menu .= "1️⃣ Global Dashboard\n";
            $menu .= "2️⃣ Financial Ranking\n";
            $menu .= "3️⃣ Export Report\n";
            $menu .= "4️⃣ Create Student Request\n";
            $menu .= "0️⃣ Quit";
        } elseif ($user->hasRole(RoleEnum::TEACHER->value)) {
             $menu .= "1️⃣ View Schedule\n";
             $menu .= "0️⃣ Quit";
        }
        
        return $menu;
    }

    protected function sendStaffMenu($session) {
        return $this->reply($session->phone_number, $this->getStaffMenuText($session), $session->institution_id);
    }

    protected function processStaffMenu($session, $text) {
        if ($text == '0') { $session->delete(); return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id); }
        // For main menu, '00' is not usually "back" but let's allow it to refresh
        if ($text == '00') return $this->sendStaffMenu($session);
        
        $user = User::find($session->user_id);
        $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);

        if ($isAdmin) {
            switch($text) {
                case '1': return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $this->getAdminStats($session->institution_id)) . "\n\n" . $this->getStaffMenuText($session), $session->institution_id);
                case '2': $session->update(['status' => 'ADMIN_RANKING_SELECT']); return $this->reply($session->phone_number, __('chatbot.admin_ranking_menu'), $session->institution_id);
                case '3': $session->update(['status' => 'ADMIN_EXPORT_SELECT']); return $this->reply($session->phone_number, __('chatbot.admin_export_menu'), $session->institution_id);
                
                case '4': 
                    $session->update(['status' => 'REQUEST_STUDENT_SEARCH']);
                    return $this->reply($session->phone_number, __('chatbot.request_search_prompt') ?? 'Enter Student Name/ID to search:', $session->institution_id);

                default: return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getStaffMenuText($session), $session->institution_id);
            }
        } elseif ($user->hasRole(RoleEnum::TEACHER->value)) {
             if ($text == '1') {
                 // Teacher Schedule Logic
                 return $this->reply($session->phone_number, "Schedule feature coming soon.\n\n" . $this->getStaffMenuText($session), $session->institution_id);
             }
             return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . $this->getStaffMenuText($session), $session->institution_id);
        }
        
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    // --- REQUEST FLOW ---
    protected function processRequestStudentSearch($session, $text)
    {
        if ($text == '0' || $text == '00') { $session->update(['status' => 'ACTIVE']); return $this->sendStaffMenu($session); }

        $instId = $session->institution_id;
        $user = User::find($session->user_id);
        
        if ($user->hasRole(RoleEnum::TEACHER->value)) {
             return $this->reply($session->phone_number, __('requests.unauthorized_teacher') . "\n\n" . $this->getStaffMenuText($session), $instId);
        }

        $query = Student::where('institution_id', $instId)
            ->where(function($q) use ($text) {
                $q->where('first_name', 'like', "%$text%")
                  ->orWhere('last_name', 'like', "%$text%")
                  ->orWhere('admission_number', $text);
            });

        $students = $query->take(5)->get();

        if ($students->isEmpty()) {
            return $this->reply($session->phone_number, __('chatbot.no_student_found_retry'), $instId);
        }

        if ($students->count() > 1) {
            $list = $students->map(fn($s) => "- " . $s->full_name . " (" . $s->admission_number . ")")->join("\n");
            return $this->reply($session->phone_number, __('chatbot.multiple_students_found') . "\n$list", $instId);
        }

        $student = $students->first();
        
        $session->update([
            'status' => 'REQUEST_TYPE_SELECT', 
            'identifier_input' => "TARGET:" . $student->id
        ]);
        
        return $this->reply($session->phone_number, __('chatbot.student_selected', ['name' => $student->full_name]) . "\n\n" . __('chatbot.request_menu'), $instId);
    }

    protected function processRequestType($session, $text) 
    {
        if ($text == '0') { 
            $session->update(['status' => 'ACTIVE']); 
            return ($session->user_type === 'student') ? $this->sendStudentMenu($session) : $this->sendStaffMenu($session);
        }
        
        $validTypes = ['1' => 'absence', '2' => 'late', '3' => 'sick', '4' => 'early_exit', '5' => 'other'];
        
        if (!isset($validTypes[$text])) {
            return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . __('chatbot.request_menu'), $session->institution_id);
        }
        
        $typeKey = $validTypes[$text];
        $targetPrefix = str_starts_with($session->identifier_input, 'TARGET:') ? $session->identifier_input . "|" : "";

        $session->update([
            'status' => 'REQUEST_REASON_SELECT',
            'identifier_input' => $targetPrefix . "REQ_TYPE:$typeKey"
        ]);
        
        return $this->reply($session->phone_number, __('requests.chatbot_ask_reason'), $session->institution_id);
    }
    
    protected function processRequestReason($session, $text) 
    {
        if ($text == '0') { 
            $session->update(['status' => 'ACTIVE']); 
            return ($session->user_type === 'student') ? $this->sendStudentMenu($session) : $this->sendStaffMenu($session);
        }

        $data = $session->identifier_input;
        $targetId = null;
        $type = 'other';

        if (str_contains($data, 'TARGET:')) {
            preg_match('/TARGET:(\d+)/', $data, $matches);
            $targetId = $matches[1] ?? null;
        }
        
        if (str_contains($data, 'REQ_TYPE:')) {
            preg_match('/REQ_TYPE:([a-z_]+)/', $data, $matches);
            $type = $matches[1] ?? 'other';
        }

        $studentId = $targetId ? $targetId : ($session->user_type === 'student' ? $session->user_id : null);
        
        if(!$studentId) {
             $session->update(['status' => 'ACTIVE']); 
             return $this->reply($session->phone_number, __('chatbot.system_error'), $session->institution_id);
        }

        $student = Student::find($studentId);
        $ticket = null;

        if($student) {
             $enrollment = $student->enrollments()->latest()->first();
             if($enrollment) {
                 $ticket = 'REQ-' . strtoupper(Str::random(8));
                 $user = User::find($session->user_id);
                 $staffId = ($session->user_type !== 'student' && $user->staff) ? $user->staff->id : null;

                 StudentRequest::create([
                    'institution_id' => $session->institution_id,
                    'student_id' => $student->id, 
                    'academic_session_id' => $enrollment->academic_session_id,
                    'type' => $type,
                    'reason' => $text, 
                    'start_date' => now(), 
                    'status' => ($staffId ? 'approved' : 'pending'), 
                    'ticket_number' => $ticket,
                    'created_by' => $session->user_id, 
                    'approved_by' => $staffId ? $session->user_id : null,
                    'approved_at' => $staffId ? now() : null,
                 ]);
             }
        }

        $session->update(['status' => 'ACTIVE']); 
        
        $mainMenu = ($session->user_type === 'student') ? $this->getMenuText($session) : $this->getStaffMenuText($session);
        $msg = __('requests.chatbot_submitted', ['ticket' => $ticket ?? 'N/A']) . "\n\n" . $mainMenu;
        
        return $this->reply($session->phone_number, $msg, $session->institution_id); 
    }

    // --- OTHER METHODS ---

    protected function getHomework($session, $student) {
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        
        $hw = Assignment::where('class_section_id', $enrollment->class_section_id)
            ->where('deadline', '>=', now())
            ->latest()->take(3)->get();
            
        if($hw->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_homework') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        
        $list = "";
        foreach($hw as $h) {
            $list .= "📚 *" . $h->subject->name . "*: " . $h->title . " (" . $h->deadline->format('d/m') . ")\n";
        }
        return $this->reply($session->phone_number, __('chatbot.homework_list', ['content' => $list]) . "\n\n" . $this->getMenuText($session), $session->institution_id);
    }
    
    protected function processPaymentMenu($session, $student) {
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        
        // Calculate Total Contract
        $fees = FeeStructure::where('grade_level_id', $enrollment->grade_level_id)
            ->where('academic_session_id', $enrollment->academic_session_id)
            ->where('institution_id', $session->institution_id)
            ->where('payment_mode', 'global') 
            ->sum('amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))->sum('amount');
        $due = $fees - $paid;
        
        return $this->reply($session->phone_number, 
             __('chatbot.payment_method_menu', ['due' => number_format($due), 'total' => number_format($fees)]), 
            $session->institution_id
        );
    }
    
    protected function processPaymentMethod($session, $text) { 
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        if ($text == '1') {
            $link = "https://e-digitex.com/checkout?id=" . base64_encode($session->institution_id);
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.payment_link', ['link' => $link]) . "\n\n" . $this->getMenuText($session), $session->institution_id);
        }
        if ($text == '2') {
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.mobile_money_instruction') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    protected function getBalance($session, $student) { 
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        
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
             ]) . "\n\n" . $this->getMenuText($session), 
            $session->institution_id
        );
    }

    protected function getReportCard($session, $student) { 
        $enrollment = $student->enrollments()->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        
        try {
            if (request()->getHost() == '127.0.0.1' || request()->getHost() == 'localhost') {
                 $url = route('reports.bulletin', ['student_id' => $student->id, 'mode' => 'single', 'report_scope' => 'trimester', 'trimester' => 1]);
                 return $this->reply($session->phone_number, __('chatbot.report_generated_local', ['url' => $url]) . "\n\n" . $this->getMenuText($session), $session->institution_id);
            }

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

            $this->notificationService->performSendFile($session->phone_number, $url, __('chatbot.result_found'), $filename, $session->institution_id);
            
            $this->reply($session->phone_number, $this->getMenuText($session), $session->institution_id);
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error("Bot Report Error: " . $e->getMessage());
            return $this->reply($session->phone_number, __('chatbot.error_occurred'), $session->institution_id);
        }
    }

    protected function getMiscFees($session, $student) { 
        $fees = FeeStructure::where('institution_id', $session->institution_id)->where('frequency', 'one_time')->get();
        if($fees->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_fees_found') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        
        $list = $fees->map(fn($f) => "- {$f->name}: {$f->amount} " . CurrencySymbol::default())->join("\n");
        return $this->reply($session->phone_number, __('chatbot.misc_fees_list', ['content' => $list]) . "\n\n" . $this->getMenuText($session), $session->institution_id);
    }

    protected function getActivities($session, $student) { 
        $events = Notice::where('institution_id', $session->institution_id)->latest()->take(5)->get();
        if($events->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_events_found') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        
        $list = $events->map(fn($e) => "📅 {$e->title} (" . $e->created_at->format('d M') . ")")->join("\n");
        return $this->reply($session->phone_number, __('chatbot.activities_list', ['content' => $list]) . "\n\n" . $this->getMenuText($session), $session->institution_id);
    }

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

            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($token);
            
            $this->notificationService->performSendImage(
                $session->phone_number, 
                $qrUrl, 
                __('chatbot.qr_caption', ['student' => $student->first_name]), 
                $session->institution_id
            );
            
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            return $this->reply($session->phone_number, __('chatbot.qr_success_menu') . "\n\n" . $this->getMenuText($session), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_otp'), $session->institution_id);
    }

    protected function processDerogation($session, $text) { 
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        $daysMap = ['1' => 7, '2' => 15, '3' => 20, '4' => 30];
        if (!isset($daysMap[$text])) return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);

        $days = $daysMap[$text];
        $ticket = 'DGR-' . strtoupper(Str::random(8));

        // Create Derogation Request
        StudentRequest::create([
            'institution_id' => $session->institution_id,
            'student_id' => $session->user_id,
            'academic_session_id' => AcademicSession::where('institution_id', $session->institution_id)->where('is_current', true)->value('id'),
            'type' => 'other',
            'reason' => "Derogation requested via Chatbot: {$days} days.",
            'start_date' => now(),
            'end_date' => now()->addDays($days),
            'status' => 'pending',
            'ticket_number' => $ticket,
            'created_by' => $session->user_id
        ]);
        
        $session->update(['status' => 'ACTIVE']); 
        return $this->reply($session->phone_number, __('chatbot.derogation_submitted', ['days' => $days, 'ticket' => $ticket]) . "\n\n" . $this->getMenuText($session), $session->institution_id); 
    }

    protected function processAdminRanking($session, $text) { 
        if ($text == '00') { $session->update(['status' => 'ACTIVE']); return $this->sendStaffMenu($session); }
        if ($text == '0') { $session->delete(); return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id); }
        
        $institutionId = $session->institution_id;
        
        if ($text == '2') { // Was 31 in old mock logic
            $grades = FeeStructure::where('institution_id', $institutionId)
                ->where('payment_mode', 'global')
                ->with('gradeLevel')
                ->get()
                ->groupBy('grade_level_id');
                
            $ranking = "Payment Ranking:\n";
            foreach($grades as $gradeId => $fees) {
                 $gradeName = $fees->first()->gradeLevel->name;
                 $totalExpected = $fees->sum('amount');
                 $ranking .= "- $gradeName: Target $totalExpected\n";
            }
            return $this->reply($session->phone_number, $ranking . "\n\n" . $this->getStaffMenuText($session), $institutionId);
        }

        $session->update(['status' => 'ACTIVE']); 
        return $this->reply($session->phone_number, "Ranking Option Selected\n\n" . $this->getStaffMenuText($session), $institutionId); 
    }

    protected function processAdminExport($session, $text) { 
        if ($text == '00') { $session->update(['status' => 'ACTIVE']); return $this->sendStaffMenu($session); }
        
        $institutionId = $session->institution_id;
        
        try {
            $stats = $this->getAdminStats($institutionId);
            $pdf = Pdf::loadView('reports.admin_summary', compact('stats'));
            
            $path = 'public/temp/Export_' . time() . '.pdf';
            if(!Storage::exists('public/temp')) Storage::makeDirectory('public/temp');
            
            Storage::put($path, $pdf->output());
            $url = url('storage/temp/Export_' . time() . '.pdf');
            
            $this->notificationService->performSendFile($session->phone_number, $url, __('chatbot.export_ready'), 'Export.pdf', $institutionId);

        } catch (\Exception $e) {
            Log::error("Export Failed: " . $e->getMessage());
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.export_failed') . "\n\n" . $this->getStaffMenuText($session), $institutionId);
        }

        $session->update(['status' => 'ACTIVE']); 
        return $this->reply($session->phone_number, __('chatbot.export_ready') . "\n\n" . $this->getStaffMenuText($session), $institutionId); 
    }
    
    protected function processAdminSchoolExport($session, $text) { return $this->processAdminExport($session, $text); }

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