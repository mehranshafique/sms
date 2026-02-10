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
use App\Models\FundRequest;
use App\Enums\CurrencySymbol;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
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
        try {
            $phone = $data['from']; // User Phone (Sender)
            $text = trim($data['body']);
            
            // 1. Clean Phone
            $phone = preg_replace('/[^0-9]/', '', $phone); 

            // 2. Find Active Session
            $session = ChatSession::where('phone_number', $phone)->first();

            // 3. Check Expiry
            if ($session && now()->gt($session->expires_at)) {
                $session->delete();
                $session = null;
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
                // AUTH
                case 'AWAITING_ID':
                    return $this->processIdentity($session, $text);
                case 'AWAITING_OTP':
                    return $this->processOtp($session, $text);
                
                // ACTIVE MENUS
                case 'ACTIVE':
                    if ($session->user_type === 'head_officer') {
                        return $this->processAdminMenu($session, $text);
                    }
                    return $this->processStudentMenu($session, $text);
                    
                // STUDENT SUB-FLOWS
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
                
                // ADMIN SUB-FLOWS
                case 'ADMIN_RANKING_SELECT':
                    return $this->processAdminRanking($session, $text);
                case 'ADMIN_EXPORT_SELECT':
                    return $this->processAdminExport($session, $text);
                case 'ADMIN_SCHOOL_SELECT':
                    return $this->processAdminSchoolExport($session, $text);

                default:
                    return $this->reply($phone, __('chatbot.unknown_state_error'), $session->institution_id);
            }

        } catch (\Throwable $e) {
            Log::error("Chatbot Critical Error: " . $e->getMessage());
            if (isset($data['from'])) {
                $phone = preg_replace('/[^0-9]/', '', $data['from']);
                return $this->reply($phone, "⚠️ An internal error occurred. Please try again later.", null);
            }
            return response()->json(['status' => 'error']);
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
                'user_type' => 'head_officer', 
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null
            ]);
            return $this->reply($phone, "Bienvenue sur la plateforme Digitex.\nVeuillez saisir votre ID Head Office pour commencer.", null);
        }

        // STUDENT FLOW
        $keyword = ChatbotKeyword::where('keyword', $textLower)->first();

        if ($keyword || str_starts_with($textLower, 'bonjour') || str_starts_with($textLower, 'digitex')) {
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

        // Error message if keyword not found
        return $this->reply($phone, "Keywords not found. Configure it from the dashboard before use.", null);
    }

    protected function processIdentity($session, $input)
    {
        if ($session->user_type === 'head_officer') {
            $user = User::where('username', $input)->orWhere('shortcode', $input)->first();
            if ($user && ($user->hasRole('Head Officer') || $user->hasRole('Super Admin'))) {
                return $this->sendOtp($session, $user, 'head_officer');
            }
            return $this->incrementAttempts($session);
        }

        // Student Check
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
        
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $session->update([
            'user_id' => $model->id,
            'user_type' => get_class($model),
            'institution_id' => $model->institution_id ?? $model->institute_id,
            'otp' => $otp,
            'status' => 'AWAITING_OTP',
            'identifier_input' => $type 
        ]);

        // Send OTP via SMS only
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

    protected function processStudentMenu($session, $text)
    {
        $student = Student::find($session->user_id);
        if (!$student) return $this->reply($session->phone_number, "Error: Student missing.", null);

        $text = strtolower(trim($text));
        
        if (in_array($text, ['logout', 'quitter', '0'])) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id);
        }

        switch ($text) {
            case '1': return $this->getHomework($session, $student);
            case '2': 
                $session->update(['status' => 'PAYMENT_METHOD_SELECT']); 
                return $this->reply($session->phone_number, __('chatbot.payment_method_menu', $this->getPaymentData($student)), $session->institution_id);
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
                return $this->reply($session->phone_number, __('chatbot.unknown_command'), $session->institution_id);
        }
    }

    // --- Student Logic Implementations ---

    protected function getPaymentData($student) {
        $total = Invoice::where('student_id', $student->id)->sum('total_amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))->sum('amount');
        $due = $total - $paid;
        return ['total' => number_format($total, 2), 'due' => number_format($due, 2)];
    }

    protected function processPaymentMethod($session, $text)
    {
        if ($text == '1') { // Visa
            $link = "https://e-digitex.com/checkout?id=" . base64_encode($session->institution_id);
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.payment_link', ['link' => $link]), $session->institution_id);
        } elseif ($text == '2') { // Mobile Money
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, __('chatbot.mobile_money_instruction'), $session->institution_id);
        } elseif ($text == '0') {
            $session->update(['status' => 'ACTIVE']);
            return $this->sendStudentMenu($session);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    /**
     * UPDATED BALANCE LOGIC:
     * Calculates balance based on Global Fee Structure (Annual Contract) similar to StudentBalanceController.
     */
    protected function getBalance($session, $student)
    {
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled'), $session->institution_id);

        $sessionId = $enrollment->academic_session_id;
        $gradeId = $enrollment->grade_level_id;
        $classId = $enrollment->class_section_id;
        $instId = $session->institution_id;

        // Calculate Contract Value (Global Fee Structures)
        $globalFeePerStudent = FeeStructure::where('institution_id', $instId)
            ->where('academic_session_id', $sessionId)
            ->where('payment_mode', 'global') // Only count Global fees (Installments are subsets)
            ->where(function($q) use ($gradeId, $classId) {
                $q->where('grade_level_id', $gradeId)
                  ->orWhere('class_section_id', $classId)
                  ->orWhere(function($sq) { 
                      $sq->whereNull('grade_level_id')->whereNull('class_section_id'); 
                  });
            })
            ->sum('amount');
            
        $contractTotal = $globalFeePerStudent;

        // Calculate Total Paid
        $totalPaid = Payment::whereHas('invoice', function($q) use ($student, $sessionId) {
            $q->where('student_id', $student->id)->where('academic_session_id', $sessionId);
        })->sum('amount');

        $remaining = $contractTotal - $totalPaid;
        if($remaining < 0) $remaining = 0;

        return $this->reply($session->phone_number, 
            "💰 *Financial Status*\n\n" .
            "📄 Annual Contract: " . number_format($contractTotal, 2) . " " . CurrencySymbol::default() . "\n" .
            "✅ Total Paid: " . number_format($totalPaid, 2) . " " . CurrencySymbol::default() . "\n" .
            "❌ Remaining: " . number_format($remaining, 2) . " " . CurrencySymbol::default(), 
            $session->institution_id
        );
    }

    protected function getReportCard($session, $student)
    {
        try {
            $enrollment = $student->enrollments()->latest()->first();
            if (!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled'), $session->institution_id);

            $data = ['student' => $student, 'enrollment' => $enrollment, 'trimester' => 1, 'data' => []];
            $pdf = Pdf::loadView('reports.bulletin_primary', $data);
            $fileName = 'bulletin_' . $student->id . '_' . time() . '.pdf';
            $path = 'public/temp/' . $fileName;
            
            \Illuminate\Support\Facades\Storage::put($path, $pdf->output());
            $publicUrl = asset('storage/temp/' . $fileName);
            
            if (request()->getHost() == '127.0.0.1' || request()->getHost() == 'localhost') {
                return $this->reply($session->phone_number, "📄 Report Link (Local): " . $publicUrl, $session->institution_id);
            }

            $this->notificationService->performSendFile($session->phone_number, $publicUrl, "📄 Result Card", "Bulletin.pdf", $session->institution_id);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error("Report Gen Error: " . $e->getMessage());
            return $this->reply($session->phone_number, "Error generating report. Please try again later.", $session->institution_id);
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
        
        $list = $fees->map(fn($f) => "- {$f->name}: {$f->amount} $")->join("\n");
        return $this->reply($session->phone_number, __('chatbot.misc_fees_list', ['content' => $list ?: 'None']), $session->institution_id);
    }

    protected function getActivities($session, $student) {
        $events = Notice::where('institution_id', $session->institution_id)->latest()->take(5)->get();
        if($events->isEmpty()) return $this->reply($session->phone_number, __('chatbot.no_events_found'), $session->institution_id);
        
        $list = $events->map(fn($e) => "📅 {$e->title} (" . $e->created_at->format('d M') . ")")->join("\n");
        return $this->reply($session->phone_number, __('chatbot.activities_list', ['content' => $list ?: 'None']), $session->institution_id);
    }

    protected function processDerogation($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        $map = ['1' => 7, '2' => 15, '3' => 20, '4' => 30];
        if (!isset($map[$text])) return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);

        $ticket = "DGR-" . rand(1000, 9999);
        $session->update(['status' => 'ACTIVE']);
        return $this->reply($session->phone_number, __('chatbot.derogation_submitted', ['days' => $map[$text], 'ticket' => $ticket]), $session->institution_id);
    }

    protected function processRequestType($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        if ($text == '4') { 
             $session->update(['status' => 'ACTIVE']);
             return $this->reply($session->phone_number, __('chatbot.sick_leave_submitted'), $session->institution_id);
        }
        if (in_array($text, ['1', '2', '3'])) {
            $session->update(['status' => 'REQUEST_REASON_SELECT', 'identifier_input' => "REQ_TYPE:$text"]); 
            return $this->reply($session->phone_number, __('chatbot.request_reason_'.$text), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
    }

    protected function processRequestReason($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        $parts = explode(':', $session->identifier_input);
        $typeCode = $parts[1] ?? '1';
        $typeNames = ['1'=>'Early Exit', '2'=>'Late', '3'=>'Absence'];
        
        $ticket = "REQ-" . rand(1000, 9999);

        $session->update(['status' => 'ACTIVE', 'identifier_input' => 'student']); 
        return $this->reply($session->phone_number, __('chatbot.request_submitted', ['type' => $typeNames[$typeCode], 'reason' => "Option $text", 'ticket' => $ticket]), $session->institution_id);
    }

    protected function processQrOtpConfirm($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        if ($text == '1') {
            $otp = rand(100000, 999999);
            $session->update(['otp' => $otp, 'status' => 'QR_OTP_INPUT']);
            
            // Force SMS for OTP
            $student = Student::find($session->user_id);
            $this->notificationService->sendOtpNotification($student, $otp);
            
            return $this->reply($session->phone_number, __('chatbot.otp_sent'), $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);
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

            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($token);
            $caption = __('chatbot.qr_caption', ['student' => $student->first_name]);
            
            $this->notificationService->performSendFile($session->phone_number, $qrUrl, $caption, "qrcode.png", $session->institution_id);
            
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
            case '1': 
                $stats = $this->getAdminStats($session->institution_id);
                return $this->reply($session->phone_number, __('chatbot.admin_dashboard', $stats), $session->institution_id);
            case '2': 
                $data = $this->getSchoolStats();
                return $this->reply($session->phone_number, __('chatbot.admin_school_stats', ['content' => $data]), $session->institution_id);
            case '3': 
                $session->update(['status' => 'ADMIN_RANKING_SELECT']);
                return $this->reply($session->phone_number, __('chatbot.admin_ranking_menu'), $session->institution_id);
            case '5': 
                $session->update(['status' => 'ADMIN_EXPORT_SELECT']);
                return $this->reply($session->phone_number, __('chatbot.admin_export_menu'), $session->institution_id);
            case '6': 
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
        if ($text == '00') { $session->update(['status' => 'ACTIVE']); return $this->sendAdminMenu($session); }
        if ($text == '0') { $session->delete(); return $this->reply($session->phone_number, __('chatbot.logout_success'), $session->institution_id); }

        $type = match($text) {
            '31' => 'Payment Rate',
            '32' => 'Enrollment',
            '33' => 'Amounts',
            default => null
        };

        if (!$type) return $this->reply($session->phone_number, __('chatbot.invalid_option'), $session->institution_id);

        $rankingData = Institution::withCount('students')->take(5)->get()
            ->sortByDesc('students_count') 
            ->map(fn($i, $k) => ($k+1).". {$i->name} - 8{$k}%")
            ->join("\n");

        return $this->reply($session->phone_number, __('chatbot.ranking_title', ['type' => $type, 'content' => $rankingData]), $session->institution_id);
    }

    protected function processAdminExport($session, $text)
    {
        if ($text == '00') { $session->update(['status' => 'ACTIVE']); return $this->sendAdminMenu($session); }
        
        if ($text == '2') { 
            $schools = Institution::take(5)->pluck('name')->map(fn($n, $k) => ($k+1)."️⃣ $n")->join("\n");
            $session->update(['status' => 'ADMIN_SCHOOL_SELECT']);
            return $this->reply($session->phone_number, __('chatbot.admin_school_selection', ['content' => $schools]), $session->institution_id);
        }

        $reportType = ($text == '1') ? 'Global' : 'Rankings';
        $fileName = "Export_{$reportType}_" . date('Y-m-d') . ".csv";
        $path = 'public/exports/' . $fileName;
        
        \Illuminate\Support\Facades\Storage::put($path, "ID,Name,Status\n1,School A,Active\n2,School B,Active");
        $url = asset('storage/exports/' . $fileName);
        
        if (request()->getHost() == '127.0.0.1') return $this->reply($session->phone_number, "📄 Export Link: " . $url, $session->institution_id);

        $this->notificationService->performSendFile($session->phone_number, $url, "📄 $reportType Report", $fileName, $session->institution_id);
        return response()->json(['status' => 'success']);
    }

    protected function processAdminSchoolExport($session, $text)
    {
        if ($text == '00') { $session->update(['status' => 'ACTIVE']); return $this->sendAdminMenu($session); }
        return $this->reply($session->phone_number, __('chatbot.export_ready'), $session->institution_id);
    }

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