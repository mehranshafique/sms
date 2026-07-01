<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\ChatbotKeyword;
use App\Models\Student;
use App\Models\StudentParent;
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
use App\Models\StaffLeave;
use App\Models\StaffAttendance;
use App\Models\Budget;
use App\Models\FundRequest;
use App\Enums\RoleEnum;
use App\Enums\InstitutionType;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

class ChatbotLogicService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected CurrencyService $currencyService
    ) {}

    protected function fmtMoney(float $amount, ?int $institutionId = null): string
    {
        return $this->currencyService->format($amount, $institutionId);
    }

    protected function currencySymbol(?int $institutionId = null): string
    {
        return $this->currencyService->getSettings($institutionId)['symbol'];
    }

    /**
     * Sanitizes dynamic strings to prevent WhatsApp API Markdown crashes
     */
    protected function sanitizeMarkdown($text) {
        if (empty($text)) return '';
        return str_replace(['*', '_', '~', '`', '[', ']'], ' ', $text);
    }

    /**
     * Main Processor & Router
     */
    public function processMessage(array $data)
    {
        try {
            $phone = $data['from'] ?? 'UNKNOWN'; 
            $text = trim($data['body'] ?? '');
            $phone = preg_replace('/[^0-9]/', '', $phone); 

            Log::info("Chatbot Lifecycle: Incoming Message", ['phone' => $phone, 'text' => $text]);

            $session = ChatSession::where('phone_number', $phone)->first();

            if ($session && now()->gt($session->expires_at)) {
                Log::info("Chatbot Lifecycle: Session Expired", ['phone' => $phone, 'expired_at' => $session->expires_at]);
                $session->delete();
                $session = null;
                $msg = ($session && $session->locale === 'en') ? "Session expired. Send 'Menu' to start again." : "Session expirée. Veuillez envoyer 'Menu' pour recommencer.";
                return $this->reply($phone, $msg, null);
            }

            if (!$session) {
                Log::info("Chatbot Lifecycle: No active session. Triggering handleNewSession.");
                return $this->handleNewSession($phone, $text);
            }

            $session->update(['last_interaction_at' => now(), 'expires_at' => now()->addMinutes(30)]);
            if ($session->locale) app()->setLocale($session->locale);

            $cmd = preg_replace('/[^a-z0-9]/', '', strtolower($text));

            Log::info("Chatbot Lifecycle: Active Session Found", [
                'status' => $session->status, 
                'user_type' => $session->user_type,
                'cleaned_command' => $cmd
            ]);

            if ($session->status !== 'AWAITING_ID' && $session->status !== 'AWAITING_OTP' && $session->status !== 'CHILD_SELECT') {
                if ($cmd === '0' || $cmd === '00' || $cmd === 'menu') {
                    Log::info("Chatbot Lifecycle: User triggered return to Main Menu.");
                    $session->update(['status' => 'ACTIVE', 'identifier_input' => null, 'otp' => null]); 
                    return $this->routeToMainMenu($session);
                }
                
                if ($cmd === '99') {
                    $newLocale = $session->locale === 'en' ? 'fr' : 'en';
                    Log::info("Chatbot Lifecycle: Language changed", ['new_locale' => $newLocale]);
                    $session->update(['locale' => $newLocale]);
                    app()->setLocale($newLocale);
                    $msg = $newLocale === 'fr' ? "✅ Langue changée en Français." : "✅ Language changed to English.";
                    $this->reply($session->phone_number, $msg, $session->institution_id);
                    return $this->routeToMainMenu($session);
                }
            }

            switch ($session->status) {
                case 'AWAITING_ID':
                    return $this->processIdentity($session, $text);
                
                case 'AWAITING_OTP':
                    return $this->processOtp($session, $text);
                
                case 'CHILD_SELECT': 
                    return $this->processChildSelection($session, $text);
                
                case 'ACTIVE':
                    return $this->processActiveMenu($session, $cmd);
                    
                case 'PAYMENT_METHOD_SELECT':
                    return $this->processPaymentMethod($session, $cmd);
                case 'DEROGATION_DURATION_SELECT':
                    return $this->processDerogation($session, $cmd);
                case 'REQUEST_TYPE_SELECT':
                    return $this->processRequestType($session, $cmd);
                case 'REQUEST_REASON_SELECT':
                    return $this->processRequestReason($session, $text); 
                case 'QR_OTP_CONFIRM':
                    return $this->processQrOtpConfirm($session, $cmd);
                case 'QR_OTP_INPUT':
                    return $this->processQrOtpInput($session, $text); 
                case 'STUDENT_SCHEDULE_SELECT':
                    return $this->processStudentScheduleSelect($session, $cmd);
                
                case 'LMD_FEES_SELECT':
                    return $this->processLmdFees($session, $cmd);
                case 'LMD_RESULTS_SELECT':
                    return $this->processLmdResults($session, $cmd);

                case 'TEACHER_ATTENDANCE_OTP':
                    return $this->processTeacherAttendanceOtp($session, $text);
                case 'TEACHER_SALARY_ADVANCE_PERCENTAGE':
                    return $this->processTeacherSalaryAdvancePercentage($session, $cmd);
                case 'TEACHER_SALARY_ADVANCE_OTP':
                    return $this->processTeacherSalaryAdvanceOtp($session, $text);
                case 'TEACHER_LEAVE_TYPE':
                    return $this->processTeacherLeaveType($session, $cmd);

                case 'HEADOFF_EFFECTIF_SELECT':
                    return $this->processHeadOffEffectif($session, $cmd);
                case 'HEADOFF_FINANCE_SELECT':
                    return $this->processHeadOffFinance($session, $cmd);
                case 'HEADOFF_BUDGET_SELECT':
                    return $this->processHeadOffBudget($session, $cmd);
                case 'ADMIN_RANKING_SELECT':
                    return $this->processAdminRanking($session, $cmd);
                case 'ADMIN_EXPORT_SELECT':
                    return $this->processAdminExport($session, $cmd);

                default:
                    Log::warning("Chatbot Lifecycle: Unhandled session status fallback triggered.", ['status' => $session->status]);
                    $session->update(['status' => 'ACTIVE']); 
                    return $this->routeToMainMenu($session);
            }

        } catch (\Throwable $e) {
            Log::error("Chatbot Critical Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            if (isset($data['from'])) {
                $p = preg_replace('/[^0-9]/', '', $data['from']);
                $fallback = "⚠️ Une erreur inattendue s'est produite. Veuillez envoyer '0' ou 'Menu' pour recommencer.\n\n⚠️ An unexpected error occurred. Please send '0' or 'Menu' to restart.";
                return $this->reply($p, $fallback, null);
            }
            return response()->json(['status' => 'error']);
        }
    }

    // =================================================================================
    // --- 1. INITIALIZATION & AUTHENTICATION ---
    // =================================================================================

    protected function handleNewSession($phone, $text)
    {
        $textLower = strtolower(trim($text));
        
        Log::info("Chatbot Flow: New Session Initiated", ['text_received' => $textLower]);

        if (in_array($textLower, ['admin', 'agent', 'staff', 'digitex'])) {
            ChatSession::create([
                'phone_number' => $phone,
                'status' => 'AWAITING_ID',
                'user_type' => 'staff', 
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null,
                'locale' => 'fr' 
            ]);
            return $this->reply($phone, "Bienvenue, veuillez entrer votre identifiant (Shortcode, ID employé ou téléphone).", null);
        }

        $keyword = ChatbotKeyword::whereRaw('LOWER(keyword) = ?', [$textLower])->first();
        
        if ($keyword) {
            $locale = $keyword->language ?? 'fr'; 
            app()->setLocale($locale);

            ChatSession::create([
                'phone_number' => $phone,
                'institution_id' => $keyword->institution_id,
                'status' => 'AWAITING_ID',
                'locale' => $locale,
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null, 
                'user_type' => 'student'
            ]);

            $msg = $keyword->welcome_message ?? ($locale === 'en' ? "Welcome! Please enter your Admission Number (Student) or Phone Number (Parent)." : "Bienvenue! Veuillez entrer votre Matricule (Elève) ou votre numéro de téléphone (Parent).");
            return $this->reply($phone, $msg, $keyword->institution_id);
        }

        if (in_array($textLower, ['bonjour', 'hello', 'menu', 'start', 'salut', 'hi', 'portail'])) {
            $locale = in_array($textLower, ['hello', 'hi', 'start']) ? 'en' : 'fr'; 
            app()->setLocale($locale);

            ChatSession::create([
                'phone_number' => $phone,
                'institution_id' => null,
                'status' => 'AWAITING_ID',
                'locale' => $locale,
                'last_interaction_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'user_id' => null, 
                'user_type' => 'student'
            ]);

            $msg = $locale === 'en' ? "Welcome! Please enter your Admission Number (Student) or Phone Number (Parent)." : "Bienvenue! Veuillez entrer votre Matricule (Elève) ou votre numéro de téléphone (Parent).";
            return $this->reply($phone, $msg, null);
        }

        Log::warning("Chatbot Flow: Keyword totally unrecognized", ['text' => $textLower]);
        $fallbackMsg = "👋 Mot clé introuvable. Envoyez le mot-clé de votre école (ou 'Bonjour' / 'Hello') pour commencer.\n\nKeyword not found. Send your school's keyword (or 'Hello') to begin.";
        return $this->reply($phone, $fallbackMsg, null);
    }

    protected function processIdentity($session, $input)
    {
        $isEn = $session->locale === 'en';
        Log::info("Chatbot Auth: Processing Identity", ['input' => $input, 'user_type' => $session->user_type]);

        if ($session->user_type === 'staff') {
            $user = app(OtpAuthService::class)->resolveUser($input, $session->institution_id);
            
            if ($user) {
                $role = 'staff';
                if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) $role = 'super_admin';
                elseif ($user->hasRole(RoleEnum::HEAD_OFFICER->value)) $role = 'head_officer';
                elseif ($user->hasRole(RoleEnum::SCHOOL_ADMIN->value)) $role = 'school_admin';
                elseif ($user->hasRole(RoleEnum::TEACHER->value)) $role = 'teacher';

                $session->update(['user_type' => $role]);
                return $this->sendOtp($session, $user, 'staff');
            }
            return $this->incrementAttempts($session, $isEn ? "Invalid ID." : "Identifiant invalide.");
        }

        $cleanInput = preg_replace('/[^0-9]/', '', $input);
        
        if (strlen($cleanInput) >= 8) { 
            $parent = StudentParent::where('father_phone', 'like', "%$cleanInput%")
                ->orWhere('mother_phone', 'like', "%$cleanInput%")
                ->orWhere('guardian_phone', 'like', "%$cleanInput%")
                ->first();

            if ($parent) {
                return $this->sendOtp($session, $parent, 'parent');
            }
        }

        $student = Student::with('parent')->where('admission_number', $input)->first();
        if ($student) {
            return $this->sendOtp($session, $student, 'student');
        }

        return $this->incrementAttempts($session, $isEn ? "ID or Phone Number not found." : "Matricule ou Numéro de téléphone introuvable.");
    }

    protected function sendOtp($session, $model, $type)
    {
        $otp = rand(100000, 999999);
        $phone = null;
        $isEn = $session->locale === 'en';
        
        if ($type === 'student') {
            $parent = $model->parent;
            $phone = $parent ? ($parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone) : null;
            $phone = $phone ?: $model->mobile_number; 
        } elseif ($type === 'parent') {
            $phone = $model->father_phone ?? $model->mother_phone ?? $model->guardian_phone;
        } else {
            $phone = $model->phone;
        }

        if (!$phone) {
            return $this->reply($session->phone_number, $isEn ? "No registered phone found for OTP." : "Aucun téléphone enregistré pour l'envoi de l'OTP.", $session->institution_id);
        }
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $session->update([
            'user_id' => $model->id,
            'user_type' => $type === 'parent' ? 'parent' : $session->user_type, 
            'institution_id' => $model->institution_id ?? $model->institute_id,
            'otp' => $otp,
            'status' => 'AWAITING_OTP',
            'identifier_input' => $type 
        ]);

        // --- NEW: Scalable OTP SMS Billing Rule ---
        if (!$this->authorizeChatbotInteraction($session->institution_id, 'sms')) {
            Log::error("Chatbot Auth Error: Insufficient SMS credits to send OTP.");
            return $this->reply($session->phone_number, $isEn ? "⚠️ Service unavailable: Insufficient school messaging credits." : "⚠️ Service indisponible: crédits de l'école épuisés.", $session->institution_id);
        }

        $otpMsg = $isEn ? "Your Digitex OTP code is: $otp" : "Votre code OTP Digitex est: $otp";
        $this->notificationService->performSend($cleanPhone, $otpMsg, $session->institution_id, true, 'sms');
        
        $masked = Str::mask($cleanPhone, '*', 3, -3);
        $confirmMsg = $isEn ? "OTP sent to $masked." : "OTP envoyé au $masked.";
        return $this->reply($session->phone_number, $confirmMsg, $session->institution_id);
    }

    protected function processOtp($session, $input)
    {
        $cleanInput = preg_replace('/[^0-9]/', '', $input);
        
        if ($cleanInput === (string)$session->otp) {
            if ($session->user_type === 'parent') {
                $session->update(['status' => 'CHILD_SELECT', 'otp' => null]);
                return $this->processChildSelection($session, null);
            }
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            return $this->routeToMainMenu($session);
        }

        $isEn = $session->locale === 'en';
        return $this->incrementAttempts($session, $isEn ? "Invalid OTP code." : "Code OTP invalide.");
    }

    // =================================================================================
    // --- 2. MULTI-CHILD SELECTION (PARENT V2 FLOW) ---
    // =================================================================================

    protected function processChildSelection($session, $text)
    {
        $isEn = $session->locale === 'en';
        $parent = StudentParent::with('students.institution')->find($session->user_id);
        
        if (!$parent || $parent->students->isEmpty()) {
            $session->delete();
            return $this->reply($session->phone_number, $isEn ? "Error: No children linked to this parent account." : "Erreur: Aucun enfant n'est lié à ce compte parent.", $session->institution_id);
        }

        $children = $parent->students;

        if (is_null($text)) {
            $msg = $isEn ? "👨‍👩‍👧 *My Children*\n\nPlease select a student profile by replying with the corresponding number:\n\n" : "👨‍👩‍👧 *Mes Enfants*\n\nVeuillez sélectionner le dossier d'un élève en répondant par le numéro correspondant:\n\n";
            foreach ($children as $index => $child) {
                $school = $this->sanitizeMarkdown(optional($child->institution)->name ?? 'School');
                $name = $this->sanitizeMarkdown($child->full_name);
                $msg .= ($index + 1) . "️⃣ " . $name . " (" . $school . ")\n";
            }
            return $this->reply($session->phone_number, $msg, $session->institution_id);
        }

        $cmd = preg_replace('/[^0-9]/', '', $text);
        $selectedIndex = ((int)$cmd) - 1;
        
        if (isset($children[$selectedIndex])) {
            $selectedChild = $children[$selectedIndex];
            $session->update([
                'user_id' => $selectedChild->id,
                'user_type' => 'student',
                'institution_id' => $selectedChild->institution_id,
                'status' => 'ACTIVE'
            ]);
            return $this->routeToMainMenu($session);
        }

        return $this->reply($session->phone_number, $isEn ? "❌ Invalid option. Please select a valid number." : "❌ Option invalide. Veuillez sélectionner un chiffre de la liste.", $session->institution_id);
    }

    // =================================================================================
    // --- 3. MASTER MENU ROUTER ---
    // =================================================================================

    protected function routeToMainMenu($session)
    {
        if ($session->user_type === 'student') {
            $student = Student::with('institution')->find($session->user_id);
            if (!$student) return $this->reply($session->phone_number, "⚠️ Profile Error. Please type 'Menu' to restart.", $session->institution_id);

            $type = optional($student->institution)->type ?? 'primary';
            $typeValue = ($type instanceof InstitutionType) ? $type->value : (is_object($type) ? $type->value : $type);
            
            if (in_array($typeValue, ['university', 'lmd'])) {
                return $this->sendLmdMenu($session);
            }
            return $this->sendStudentMenu($session);
        } 
        
        return $this->sendStaffMenu($session);
    }

    protected function processActiveMenu($session, $cmd)
    {
        $isEn = $session->locale === 'en';

        if (in_array($cmd, ['logout', 'quitter', 'exit'])) {
            $session->delete();
            return $this->reply($session->phone_number, $isEn ? "👋 Session closed." : "👋 Session fermée.", $session->institution_id);
        }

        if ($session->user_type === 'student') {
            $student = Student::with('institution')->find($session->user_id);
            if (!$student) return $this->reply($session->phone_number, "⚠️ Profile Error.", $session->institution_id);
            
            $type = optional($student->institution)->type ?? 'primary';
            $typeValue = ($type instanceof InstitutionType) ? $type->value : (is_object($type) ? $type->value : $type);

            if (in_array($typeValue, ['university', 'lmd'])) {
                return $this->processLmdMenu($session, $cmd);
            }
            return $this->processStudentMenu($session, $cmd);
        }
        
        return $this->processStaffMenu($session, $cmd);
    }

    protected function getReturnPrompt($session) {
        $msg = optional($session)->locale === 'en' ? "Return to Menu" : "Retour au Menu";
        return "\n\n👉 0️⃣ " . $msg;
    }

    // =================================================================================
    // --- 4. PRIMARY / SECONDARY STUDENT (V2) ---
    // =================================================================================

    protected function getMenuText($session) {
        try {
            $student = Student::find($session->user_id);
            if (!$student) return "⚠️ Error: Profile missing.";
            
            $enrollment = $student->enrollments()->latest()->first();
            $info = "";
            $year = date('Y');
            
            if ($enrollment) {
                $className = optional($enrollment->classSection)->name ?? '';
                $gradeName = optional(optional($enrollment->classSection)->gradeLevel)->name ?? '';
                $info = trim("$gradeName $className");
                $year = optional($enrollment->academicSession)->name ?? date('Y');
            }

            $isEn = $session->locale === 'en';
            
            $school = $this->sanitizeMarkdown(optional($student->institution)->name ?? 'School');
            $name = $this->sanitizeMarkdown($student->full_name);
            $infoStr = $this->sanitizeMarkdown($info);

            if ($isEn) {
                $menu = "🎓 *Student / Parent Portal*\n🏫 {$school}\n👤 {$name}\n📘 {$infoStr} ({$year})\n\n";
                $menu .= "Please choose an option:\n\n";
                $menu .= "1️⃣ Homework & Assignments\n";
                $menu .= "2️⃣ Check Fee Balance\n";
                $menu .= "3️⃣ My Payments\n";
                $menu .= "4️⃣ Miscellaneous Fees\n";
                $menu .= "5️⃣ Request Deadline Extension\n";
                $menu .= "6️⃣ My Leave Requests\n";
                $menu .= "7️⃣ Timetable & Exams\n";
                $menu .= "8️⃣ Academic Report Card\n";
                $menu .= "9️⃣ Generate Pickup QR Code\n";
                $menu .= "\n9️⃣9️⃣ 🌐 Changer de langue (FR)\n🚪 Send *logout* to quit";
            } else {
                $menu = "🎓 *Portail Parents / Élèves*\n🏫 {$school}\n👤 {$name}\n📘 {$infoStr} ({$year})\n\n";
                $menu .= "Veuillez choisir une option:\n\n";
                $menu .= "1️⃣ e-TD / e-Devoir\n";
                $menu .= "2️⃣ Connaitre les frais (Balance)\n";
                $menu .= "3️⃣ Mes Paiements\n";
                $menu .= "4️⃣ Autres frais (Divers)\n";
                $menu .= "5️⃣ Dérogation\n";
                $menu .= "6️⃣ Mes requêtes\n";
                $menu .= "7️⃣ Horaires (Cours & Examens)\n";
                $menu .= "8️⃣ e-Bulletin\n";
                $menu .= "9️⃣ QR Code Retrait enfant\n";
                $menu .= "\n9️⃣9️⃣ 🌐 Change Language (EN)\n🚪 Envoyer *logout* pour quitter";
            }

            return $menu;
        } catch (\Exception $e) {
            return "⚠️ Erreur lors du chargement du menu. Envoyez '0' pour recommencer.";
        }
    }

    protected function sendStudentMenu($session) {
        return $this->reply($session->phone_number, $this->getMenuText($session), $session->institution_id);
    }

    protected function processStudentMenu($session, $cmd)
    {
        $student = Student::find($session->user_id);
        if (!$student) return $this->reply($session->phone_number, "Error: Profile missing.", $session->institution_id);
        
        $isEn = $session->locale === 'en';
        
        switch ($cmd) {
            case '1': return $this->getHomework($session, $student);
            case '2': return $this->getBalance($session, $student);
            case '3': return $this->getPaymentHistory($session, $student);
            case '4': return $this->getMiscFees($session, $student);
            case '5': 
                $session->update(['status' => 'DEROGATION_DURATION_SELECT']); 
                $msg = $isEn ? "⏳ *Deadline Extension Request*\nChoose duration:\n1️⃣ 3 Days\n2️⃣ 7 Days\n3️⃣ 10 Days\n4️⃣ 14 Days" : "⏳ *Demande de Dérogation*\nChoisissez la durée:\n1️⃣ 3 Jours\n2️⃣ 7 Jours\n3️⃣ 10 Jours\n4️⃣ 14 Jours";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
            case '6': 
                $session->update(['status' => 'REQUEST_TYPE_SELECT']); 
                $msg = $isEn ? "📨 *My Requests*\n1️⃣ Early Exit\n2️⃣ Medical / Hospital\n3️⃣ Late\n4️⃣ Absence" : "📨 *Mes Requêtes*\n1️⃣ Sortie Anticipée\n2️⃣ Maladie / Hôpital\n3️⃣ Retard\n4️⃣ Absence";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
            case '7': 
                $session->update(['status' => 'STUDENT_SCHEDULE_SELECT']);
                $msg = $isEn ? "📅 *Schedules*\n1️⃣ Today's Classes\n2️⃣ Upcoming Exams" : "📅 *Horaires*\n1️⃣ Cours (Aujourd'hui)\n2️⃣ Epreuves/Examen (A venir)";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
            case '8': 
                return $this->getReportCard($session, $student); 
            case '9': 
                $session->update(['status' => 'QR_OTP_CONFIRM']); 
                $msg = $isEn ? "🔐 *Generate Pickup QR*\n1️⃣ Request OTP Code" : "🔐 *Générer un QR Code de Retrait*\n1️⃣ Demander un code de sécurité OTP";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
            default: 
                $msg = $isEn ? "Invalid option. Please check the menu and try again." : "Option invalide. Veuillez vérifier le menu et réessayer.";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
    }

    protected function processStudentScheduleSelect($session, $cmd) {
        $student = Student::find($session->user_id);
        $isEn = $session->locale === 'en';

        if ($cmd == '1') {
            return $this->getStudentTimetable($session, $student);
        } elseif ($cmd == '2') {
            return $this->getStudentUpcomingExams($session, $student);
        }
        
        return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
    }

    // =================================================================================
    // --- 5. UNIVERSITY / LMD STUDENT (V2) ---
    // =================================================================================

    protected function getLmdMenuText($session) {
        try {
            $student = Student::find($session->user_id);
            if (!$student) return "⚠️ Error: Profile missing.";
            
            $school = $this->sanitizeMarkdown(optional($student->institution)->name ?? 'Université');
            $name = $this->sanitizeMarkdown($student->full_name);
            $isEn = $session->locale === 'en';

            if ($isEn) {
                $menu = "🎓 *University Portal (LMD)*\n🏫 {$school}\n👤 {$name}\n\n";
                $menu .= "Please choose an option:\n\n";
                $menu .= "1️⃣ Academic Fees\n";
                $menu .= "2️⃣ Schedules (Classes & Exams)\n";
                $menu .= "3️⃣ Academic Results\n";
                $menu .= "4️⃣ Academic Work (Assignments)\n";
                $menu .= "\n9️⃣9️⃣ 🌐 Changer de langue (FR)\n🚪 Send *logout* to quit";
            } else {
                $menu = "🎓 *Portail Etudiant (LMD)*\n🏫 {$school}\n👤 {$name}\n\n";
                $menu .= "Veuillez choisir une option:\n\n";
                $menu .= "1️⃣ Frais Académiques\n";
                $menu .= "2️⃣ Horaires (Cours & Examens)\n";
                $menu .= "3️⃣ Résultats Académiques\n";
                $menu .= "4️⃣ Travaux Académiques (TP/Devoirs)\n";
                $menu .= "\n9️⃣9️⃣ 🌐 Change Language (EN)\n🚪 Envoyer *logout* pour quitter";
            }

            return $menu;
        } catch (\Exception $e) {
            return "⚠️ Erreur lors du chargement du menu. Envoyez '0' pour recommencer.";
        }
    }

    protected function sendLmdMenu($session) {
        return $this->reply($session->phone_number, $this->getLmdMenuText($session), $session->institution_id);
    }

    protected function processLmdMenu($session, $cmd)
    {
        $student = Student::find($session->user_id);
        if (!$student) return $this->reply($session->phone_number, "Error: Profile missing.", $session->institution_id);
        
        $isEn = $session->locale === 'en';
        
        switch ($cmd) {
            case '1': 
                $session->update(['status' => 'LMD_FEES_SELECT']);
                $msg = $isEn ? "💰 *Academic Fees*\n1️⃣ Tuition\n2️⃣ Enrollment\n3️⃣ Other Fees\n4️⃣ My Payments" : "💰 *Frais Académiques*\n1️⃣ Minerval\n2️⃣ Enrôlement\n3️⃣ Autres Frais\n4️⃣ Mes Paiements";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
            case '2': 
                $session->update(['status' => 'STUDENT_SCHEDULE_SELECT']);
                $msg = $isEn ? "📅 *Schedules*\n1️⃣ Classes (Today)\n2️⃣ Exams" : "📅 *Horaires*\n1️⃣ Cours (Aujourd'hui)\n2️⃣ Examens / Épreuves";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
            case '3': 
                $session->update(['status' => 'LMD_RESULTS_SELECT']);
                $msg = $isEn ? "📊 *Academic Results*\n1️⃣ Semester I\n2️⃣ Semester II\n3️⃣ Global Transcript" : "📊 *Résultats Académiques*\n1️⃣ Semestre I\n2️⃣ Semestre II\n3️⃣ Moyenne & Transcript Global";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
            case '4': 
                return $this->getHomework($session, $student);
            default: 
                return $this->reply($session->phone_number, ($isEn ? "Invalid option. Please check the menu and try again." : "Option invalide. Veuillez vérifier le menu et réessayer.") . $this->getReturnPrompt($session), $session->institution_id);
        }
    }

    protected function processLmdFees($session, $cmd) {
        $student = Student::find($session->user_id);
        $isEn = $session->locale === 'en';

        if ($cmd == '1' || $cmd == '2' || $cmd == '3') {
            return $this->getBalance($session, $student); 
        } elseif ($cmd == '4') {
            return $this->getPaymentHistory($session, $student);
        }
        return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processLmdResults($session, $cmd) {
        $student = Student::find($session->user_id);
        $institutionId = $session->institution_id;
        $isEn = $session->locale === 'en';
        
        $isBlocked = InstitutionSetting::where('institution_id', $institutionId)->where('key', 'block_reports_on_debt')->value('value');
        if ($isBlocked == '1') {
            $unpaid = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total_amount - paid_amount'));
            if ($unpaid > 0) {
                $currency = $this->currencySymbol($institutionId);
                $msg = $isEn ? "⛔ Access denied. You have an outstanding balance of " . number_format($unpaid, 2) . $currency . ". Please settle to view results." 
                             : "⛔ Accès refusé. Vous avez un solde impayé de " . number_format($unpaid, 2) . $currency . ". Veuillez régler pour voir vos résultats.";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
            }
        }

        try {
            $originalUser = Auth::user();
            $originalSession = session('active_institution_id');
            
            $superAdmin = User::role('Super Admin')->first();
            if ($superAdmin) Auth::login($superAdmin);
            session(['active_institution_id' => $institutionId]);

            $reportRequest = \Illuminate\Http\Request::create('/dummy', 'GET', ['student_id' => $student->id]);
            $controller = app(\App\Http\Controllers\ReportController::class);
            $response = $controller->transcript($reportRequest);
            
            if ($originalUser) Auth::login($originalUser); else Auth::logout();
            session(['active_institution_id' => $originalSession]);

            if ($response instanceof \Illuminate\Http\RedirectResponse || (isset($response->getData()['status']) && $response->getData()['status'] == 'error')) {
                return $this->reply($session->phone_number, ($isEn ? "No records found." : "Aucun enregistrement trouvé."), $institutionId);
            }

            $pdfContent = $response->getContent();
            $filename = "Transcript_{$student->admission_number}_" . time() . ".pdf";
            $path = "temp/{$filename}";
            
            if (!Storage::disk('public')->exists('temp')) Storage::disk('public')->makeDirectory('temp');
            Storage::disk('public')->put($path, $pdfContent);
            
            $downloadUrl = asset('storage/' . $path);
            $caption = $isEn ? "Here is your LMD Transcript." : "Voici votre relevé LMD.";
            
            return $this->replyWithFile($session->phone_number, $downloadUrl, $caption . $this->getReturnPrompt($session), $filename, $institutionId);

        } catch (\Exception $e) {
            if (isset($originalUser) && $originalUser) Auth::login($originalUser);
            if (isset($originalSession)) session(['active_institution_id' => $originalSession]);
            
            $msg = $isEn ? "An error occurred generating your transcript." : "Une erreur s'est produite lors de la génération du relevé.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
        }
    }


    // =================================================================================
    // --- 6. STAFF / HR / ADMIN (V2) ---
    // =================================================================================

    protected function getStaffMenuText($session) {
        try {
            $user = User::find($session->user_id);
            if (!$user) return "⚠️ Error: Profile missing.";
            
            $role = $session->user_type; 
            $isEn = $session->locale === 'en';
            
            $name = $this->sanitizeMarkdown($user->name);
            $menu = $isEn ? "👤 *Welcome, " . $name . "*\n\n" : "👤 *Bienvenue, " . $name . "*\n\n";

            if ($role === 'super_admin') {
                $menu .= $isEn ? "🛠 *Super Admin Menu*\n\n1️⃣ Global Finance\n2️⃣ Subscriptions\n3️⃣ Schools\n4️⃣ SMS/WhatsApp Credits"
                               : "🛠 *Menu Super Admin*\n\n1️⃣ Finance Globale\n2️⃣ Subscriptions\n3️⃣ Ecoles\n4️⃣ Crédits SMS/WhatsApp";
            } 
            elseif ($role === 'head_officer') {
                $menu .= $isEn ? "🏢 *Head Officer Menu*\n\n1️⃣ Enrollments (Global & Branch)\n2️⃣ Fee Payments (Daily Cash)\n3️⃣ Budget & Finance\n4️⃣ Branch Rankings"
                               : "🏢 *Menu Direction Générale*\n\n1️⃣ Effectifs (Global & Par Ecole)\n2️⃣ Paiement Frais (Caisses du jour)\n3️⃣ Budget & Finance\n4️⃣ Classements (Ecoles)";
            }
            elseif ($role === 'school_admin') {
                $menu .= $isEn ? "🏫 *Director Menu*\n\n1️⃣ Total Enrollment & Classes\n2️⃣ Daily Cash & Forecast\n3️⃣ Debtors List\n4️⃣ Today's Attendance"
                               : "🏫 *Menu Directeur*\n\n1️⃣ Effectif Global & Par Classe\n2️⃣ Etat Caisse & Prévision\n3️⃣ Elèves Débiteurs\n4️⃣ Présences du Jour";
            } 
            elseif ($role === 'teacher') {
                $menu .= $isEn ? "📚 *Teacher & Staff Menu*\n\n1️⃣ Mark Attendance (OTP)\n2️⃣ My Timetable\n3️⃣ My Exams\n4️⃣ My Leave Requests\n5️⃣ Request Salary Advance"
                               : "📚 *Menu Enseignant / Agent*\n\n1️⃣ Pointer présence (OTP)\n2️⃣ Mes Horaires\n3️⃣ Mes Epreuves\n4️⃣ Mes Requêtes (Congé/Maladie)\n5️⃣ Avance sur Salaire";
            }

            $menu .= $isEn ? "\n\n9️⃣9️⃣ 🌐 Changer de langue (FR)\n🚪 Send *logout* to quit" 
                           : "\n\n9️⃣9️⃣ 🌐 Change Language (EN)\n🚪 Envoyer *logout* pour quitter";
            return $menu;
        } catch (\Exception $e) {
            return "⚠️ Erreur lors du chargement du menu. Envoyez '0' pour recommencer.";
        }
    }

    protected function sendStaffMenu($session) {
        return $this->reply($session->phone_number, $this->getStaffMenuText($session), $session->institution_id);
    }

    protected function processStaffMenu($session, $cmd)
    {
        $role = $session->user_type;
        $user = User::find($session->user_id);
        if (!$user) return $this->reply($session->phone_number, "Error: Profile missing.", $session->institution_id);
        
        $isEn = $session->locale === 'en';

        if ($role === 'super_admin') {
            switch($cmd) {
                case '1': 
                    $invoiced = Subscription::sum('price_paid');
                    $msg = $isEn ? "💰 *Global Finance*\nRevenue: " : "💰 *Finance Globale*\nRevenus: ";
                    return $this->reply($session->phone_number, $msg . number_format($invoiced, 2) . " " . $this->currencySymbol($session->institution_id) . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $active = Subscription::where('status', 'active')->where('end_date', '>=', now())->count();
                    $expired = Subscription::where('end_date', '<', now())->count();
                    $msg = $isEn ? "📦 *Subscriptions*\nActive: $active\nExpired: $expired" : "📦 *Subscriptions*\nActives: $active\nExpirées: $expired";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $activeInst = Institution::where('is_active', true)->count();
                    $inactiveInst = Institution::where('is_active', false)->count();
                    $msg = $isEn ? "🏫 *Schools*\nActive: $activeInst\nInactive: $inactiveInst" : "🏫 *Ecoles*\nActives: $activeInst\nInactives: $inactiveInst";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '4': 
                    $sms = Institution::sum('sms_credits');
                    $wa = Institution::sum('whatsapp_credits');
                    $msg = $isEn ? "📲 *Credits*\nSMS Allocated: $sms\nWhatsApp Allocated: $wa" : "📲 *Crédits*\nSMS Alloués: $sms\nWhatsApp Alloués: $wa";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
            }
        }

        elseif ($role === 'head_officer') {
            switch($cmd) {
                case '1': 
                    $session->update(['status' => 'HEADOFF_EFFECTIF_SELECT']);
                    $msg = $isEn ? "📊 *Enrollments*\n1️⃣ Global\n2️⃣ Per Branch / Class" : "📊 *Effectifs*\n1️⃣ Global\n2️⃣ Par Ecoles / Classes";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $session->update(['status' => 'HEADOFF_FINANCE_SELECT']);
                    $msg = $isEn ? "💰 *Fee Payments*\n1️⃣ Global & Forecast\n2️⃣ Daily Cash\n3️⃣ Debtors List" : "💰 *Paiement Frais*\n1️⃣ Global & Prévision\n2️⃣ Etat caisses du jour\n3️⃣ Elèves débiteurs";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $session->update(['status' => 'HEADOFF_BUDGET_SELECT']);
                    $msg = $isEn ? "🏦 *Budget & Finance*\n1️⃣ Global Budget\n2️⃣ Budget per Branch\n3️⃣ Global Expense\n4️⃣ Expense per Branch\n5️⃣ Pending Fund Requests" 
                                 : "🏦 *Budget & Finance*\n1️⃣ Budget global\n2️⃣ Budget par école\n3️⃣ Dépense globale\n4️⃣ Dépense par école\n5️⃣ Demandes de fonds encours";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '4': 
                    $session->update(['status' => 'ADMIN_RANKING_SELECT']); 
                    $msg = $isEn ? "🏆 *Rankings*\n1️⃣ By Enrollment\n2️⃣ By Revenue" : "🏆 *Classements*\n1️⃣ Par Effectif\n2️⃣ Par Paiements";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
            }
        }
        
        elseif ($role === 'school_admin') {
            switch($cmd) {
                case '1': 
                    $students = Student::where('institution_id', $session->institution_id)->count();
                    $staff = \App\Models\Staff::where('institution_id', $session->institution_id)->count();
                    $msg = $isEn ? "👥 *Global Enrollment*\nStudents: $students\nStaff: $staff" : "👥 *Effectif Global*\nElèves: $students\nEnseignants/Staff: $staff";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $todayCash = Payment::where('institution_id', $session->institution_id)->whereDate('payment_date', today())->sum('amount');
                    $msg = $isEn ? "💵 *Daily Cash*\nToday's Collection: " : "💵 *Etat Caisse du Jour*\nRecettes du jour: ";
                    return $this->reply($session->phone_number, $msg . number_format($todayCash, 2) . " " . $this->currencySymbol($session->institution_id) . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $debtors = Invoice::where('institution_id', $session->institution_id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->distinct('student_id')->count('student_id');
                    $msg = $isEn ? "🚨 *Debtors List*\nStudents with unpaid invoices: $debtors" : "🚨 *Elèves Débiteurs*\nNombre d'élèves avec factures impayées: $debtors";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '4': 
                    $present = StudentAttendance::where('institution_id', $session->institution_id)->whereDate('attendance_date', today())->where('status', 'present')->count();
                    $msg = $isEn ? "✅ *Today's Attendance*\nPresent students: $present" : "✅ *Présences du Jour*\nElèves présents aujourd'hui: $present";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
            }
        }
        
        elseif ($role === 'teacher') {
            $staffId = optional($user->staff)->id;
            if (!$staffId) {
                return $this->reply($session->phone_number, "❌ Erreur: Profil Staff introuvable.", $session->institution_id);
            }

            switch($cmd) {
                case '1': 
                    $otp = rand(100000, 999999);
                    $session->update(['status' => 'TEACHER_ATTENDANCE_OTP', 'otp' => $otp]);
                    
                    if (!$this->authorizeChatbotInteraction($session->institution_id, 'sms')) {
                        return $this->reply($session->phone_number, $isEn ? "⚠️ Cannot send OTP: Insufficient SMS credits." : "⚠️ Envoi impossible: crédits SMS épuisés.", $session->institution_id);
                    }

                    $otpMsg = $isEn ? "Your OTP to mark attendance is: $otp" : "Votre code OTP pour pointer la présence est : $otp";
                    $this->notificationService->performSend($user->phone ?? $session->phone_number, $otpMsg, $session->institution_id, true, 'sms');
                    
                    $msg = $isEn ? "🔒 Please enter the OTP code sent via SMS to validate your attendance for today." : "🔒 Veuillez saisir le code OTP envoyé par SMS pour valider votre présence d'aujourd'hui.";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $today = strtolower(now()->format('l'));
                    $tt = Timetable::with(['subject', 'classSection'])->where('teacher_id', $staffId)->where('day_of_week', $today)->orderBy('start_time')->get();
                    $msg = $isEn ? "📅 *My Timetable (Today)*\n\n" : "📅 *Mes Horaires (Aujourd'hui)*\n\n";
                    $msg .= $tt->isEmpty() ? ($isEn ? "No classes scheduled." : "Aucun cours.") : $tt->map(fn($t) => "🕒 {$t->start_time->format('H:i')} - " . optional($t->subject)->name)->join("\n\n");
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    return $this->getTeacherExams($session, $staffId);
                case '4':
                    $session->update(['status' => 'TEACHER_LEAVE_TYPE']);
                    $msg = $isEn ? "📝 *My Leave Requests*\n1️⃣ Leave / Vacation\n2️⃣ Report Sickness\n3️⃣ Personal Emergency\n4️⃣ Late Arrival" : "📝 *Mes Requêtes*\n1️⃣ Demande de congé\n2️⃣ Signaler maladie\n3️⃣ Empêchement\n4️⃣ Retard";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '5':
                    $session->update(['status' => 'TEACHER_SALARY_ADVANCE_PERCENTAGE']);
                    $msg = $isEn ? "💸 *Salary Advance*\nChoose percentage:\n1️⃣ 50%\n2️⃣ 30%\n3️⃣ 20%\n4️⃣ 10%" : "💸 *Avance sur salaire*\nChoisissez le pourcentage:\n1️⃣ 50%\n2️⃣ 30%\n3️⃣ 20%\n4️⃣ 10%";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
            }
        }

        $fallback = $isEn ? "Unknown command. Please reply with a valid number from the menu." : "Commande inconnue. Veuillez répondre avec un numéro valide du menu.";
        return $this->reply($session->phone_number, $fallback . $this->getReturnPrompt($session), $session->institution_id);
    }

    // --- HEADOFF SUBFLOWS ---
    protected function processHeadOffEffectif($s, $t) {
        $isEn = $s->locale === 'en';
        if ($t == '1') {
            $count = Student::where('institution_id', $s->institution_id)->count();
            $msg = $isEn ? "👥 *Global Enrollment*: $count students." : "👥 *Effectif Global*: $count élèves.";
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '2') {
            $msg = $isEn ? "🏫 *Enrollment Per Class*\n" : "🏫 *Effectif Par Classe*\n";
            $classes = ClassSection::where('institution_id', $s->institution_id)->get();
            foreach($classes as $c) {
                $count = StudentEnrollment::where('class_section_id', $c->id)->where('status', 'active')->count();
                $msg .= "- {$c->name}: $count \n";
            }
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        }
        return $this->reply($s->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($s), $s->institution_id);
    }

    protected function processHeadOffFinance($s, $t) {
        $isEn = $s->locale === 'en';
        if ($t == '1') {
            $expected = Invoice::where('institution_id', $s->institution_id)->sum('total_amount');
            $collected = Invoice::where('institution_id', $s->institution_id)->sum('paid_amount');
            $msg = $isEn ? "📊 *Global Forecast*\n- Expected: " : "📊 *Global & Prévision*\n- Attendu: ";
            $msg .= number_format($expected, 2) . "\n- " . ($isEn ? "Collected: " : "Percu: ") . number_format($collected, 2) . "\n- " . ($isEn ? "Remaining: " : "Reste: ") . number_format($expected - $collected, 2);
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '2') {
            $todayCash = Payment::where('institution_id', $s->institution_id)->whereDate('payment_date', today())->sum('amount');
            $msg = $isEn ? "💵 *Daily Cash*: " : "💵 *Caisse du Jour*: ";
            return $this->reply($s->phone_number, $msg . number_format($todayCash, 2) . $this->currencySymbol($s->institution_id) . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '3') {
            $debtors = Invoice::where('institution_id', $s->institution_id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->distinct('student_id')->count('student_id');
            $msg = $isEn ? "🚨 *Debtors*: $debtors students owe fees." : "🚨 *Elèves Débiteurs*: $debtors élèves avec un solde impayé.";
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        }
        return $this->reply($s->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($s), $s->institution_id);
    }

    protected function processHeadOffBudget($s, $t) {
        $isEn = $s->locale === 'en';
        $user = User::find($s->user_id);
        $schools = clone $user->institutes; 
        if (!$schools || $schools->isEmpty()) {
            $schools = Institution::where('id', $s->institution_id)->get();
        }

        if ($t == '1') {
            $budgets = Budget::whereIn('institution_id', $schools->pluck('id'))->sum('allocated_amount');
            $msg = $isEn ? "🏦 *Total Allocated Budget*: " : "🏦 *Budget Global Alloué*: ";
            return $this->reply($s->phone_number, $msg . number_format($budgets, 2) . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '2') {
            $msg = $isEn ? "🏦 *Budget Per Branch*\n" : "🏦 *Budget Par Ecole*\n";
            foreach($schools as $school) {
                $b = Budget::where('institution_id', $school->id)->sum('allocated_amount');
                $msg .= "- {$school->name}: " . number_format($b, 2) . "\n";
            }
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '3') {
            $spent = Budget::whereIn('institution_id', $schools->pluck('id'))->sum('spent_amount');
            $msg = $isEn ? "💸 *Global Expenses*: " : "💸 *Dépense Globale*: ";
            return $this->reply($s->phone_number, $msg . number_format($spent, 2) . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '4') {
            $msg = $isEn ? "💸 *Expenses Per Branch*\n" : "💸 *Dépense Par Ecole*\n";
            foreach($schools as $school) {
                $b = Budget::where('institution_id', $school->id)->sum('spent_amount');
                $msg .= "- {$school->name}: " . number_format($b, 2) . "\n";
            }
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '5') {
            $pendingReq = FundRequest::whereIn('institution_id', $schools->pluck('id'))->where('status', 'pending')->count();
            $msg = $isEn ? "⏳ *Pending Fund Requests*: $pendingReq requests." : "⏳ *Demandes encours*: $pendingReq demandes de fonds en attente.";
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        }
        return $this->reply($s->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($s), $s->institution_id);
    }

    protected function processAdminRanking($s, $t) { 
        $isEn = $s->locale === 'en';
        $institutionId = $s->institution_id;
        $classes = ClassSection::where('institution_id', $institutionId)->get();
        $ranking = [];
        
        foreach($classes as $class) {
            $studentIds = StudentEnrollment::where('class_section_id', $class->id)->where('status', 'active')->pluck('student_id');
            if($studentIds->isEmpty()) continue;
            
            if ($t == '2') { 
                $val = Payment::where('institution_id', $institutionId)->whereHas('invoice', fn($q) => $q->whereIn('student_id', $studentIds))->sum('amount');
            } else { 
                $val = $studentIds->count();
            }
            $ranking[] = ['name' => $class->name, 'val' => $val];
        }
        
        usort($ranking, fn($a, $b) => $b['val'] <=> $a['val']);
        
        $msg = $isEn ? "🏆 *Class Rankings*\n" : "🏆 *Classement des Classes*\n";
        foreach(array_slice($ranking, 0, 10) as $idx => $r) {
            if ($t == '2') {
                $fmt = number_format($r['val'], 2) . $this->currencySymbol($institutionId);
            } else {
                $fmt = $r['val'] . ($isEn ? " students" : " élèves");
            }
            $msg .= ($idx + 1) . ". {$r['name']} - " . $fmt . "\n";
        }
        
        return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $institutionId); 
    }

    // --- TEACHER HR SUBFLOWS (V2) ---
    protected function processTeacherAttendanceOtp($session, $text) {
        $isEn = $session->locale === 'en';
        $cleanInput = preg_replace('/[^0-9]/', '', $text);
        if ($cleanInput === (string)$session->otp) {
            $user = User::find($session->user_id);
            StaffAttendance::updateOrCreate(
                [
                    'institution_id' => $session->institution_id,
                    'staff_id' => optional($user->staff)->id ?? 0,
                    'attendance_date' => today(),
                ],
                [
                    'status' => 'present',
                    'check_in' => now(),
                    'method' => 'chatbot',
                    'marked_by' => $user->id,
                ]
            );
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            $msg = $isEn ? "✅ Attendance marked successfully for today." : "✅ Présence marquée avec succès pour aujourd'hui.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        return $this->reply($session->phone_number, ($isEn ? "❌ Invalid OTP." : "❌ OTP invalide.") . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getTeacherExams($session, $staffId) {
        $isEn = $session->locale === 'en';
        $tt_classes = Timetable::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
        $alloc_classes = ClassSubject::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
        $classIds = array_unique(array_merge($tt_classes, $alloc_classes));
        
        $exams = ExamSchedule::with(['subject', 'classSection'])
            ->whereIn('class_section_id', $classIds)
            ->where('exam_date', '>=', today())
            ->orderBy('exam_date')
            ->take(6)->get();
            
        if ($exams->isEmpty()) {
            $msg = $isEn ? "No upcoming exams scheduled for your classes." : "Aucune épreuve prévue prochainement pour vos classes.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        
        $msg = $isEn ? "📝 *My Upcoming Exams*\n" : "📝 *Mes Prochaines Épreuves*\n";
        foreach($exams as $e) {
            $date = Carbon::parse($e->exam_date)->format('d/m');
            $msg .= "- " . optional($e->classSection)->name . " | " . optional($e->subject)->name . " : {$date}\n";
        }
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processTeacherSalaryAdvancePercentage($session, $cmd) {
        $isEn = $session->locale === 'en';
        $percentages = ['1' => 50, '2' => 30, '3' => 20, '4' => 10];
        if (!isset($percentages[$cmd])) return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
        
        $otp = rand(100000, 999999);
        $user = User::find($session->user_id);
        
        $session->update(['status' => 'TEACHER_SALARY_ADVANCE_OTP', 'identifier_input' => $percentages[$cmd], 'otp' => $otp]);
        
        if (!$this->authorizeChatbotInteraction($session->institution_id, 'sms')) {
            return $this->reply($session->phone_number, $isEn ? "⚠️ Cannot send OTP: Insufficient SMS credits." : "⚠️ Envoi impossible: crédits SMS épuisés.", $session->institution_id);
        }

        $otpMsg = $isEn ? "Your OTP for the salary advance is: $otp" : "Votre code OTP pour l'avance sur salaire est : $otp";
        $this->notificationService->performSend($user->phone ?? $session->phone_number, $otpMsg, $session->institution_id, true, 'sms');
        
        $msg = $isEn ? "🔒 An OTP code has been sent via SMS. Enter it to confirm your {$percentages[$cmd]}% advance request." : "🔒 Un code OTP vous a été envoyé par SMS. Saisissez-le pour confirmer votre avance de {$percentages[$cmd]}%.";
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processTeacherSalaryAdvanceOtp($session, $text) {
        $isEn = $session->locale === 'en';
        $cleanInput = preg_replace('/[^0-9]/', '', $text);
        if ($cleanInput === (string)$session->otp) {
            $user = User::find($session->user_id);
            $percentage = $session->identifier_input;
            $ticket = 'SAL-' . strtoupper(Str::random(4));
            
            $leave = StaffLeave::create([
                'institution_id' => $session->institution_id,
                'staff_id' => optional($user->staff)->id ?? 0,
                'type' => 'other',
                'reason' => "Demande d'avance sur salaire de {$percentage}% (Soumis via Chatbot). Ticket: $ticket",
                'start_date' => now(),
                'status' => 'pending'
            ]);

            app(InAppNotificationService::class)->notifyStaffLeaveSubmitted($leave);
            
            $msg = $isEn ? "✅ Request received successfully.\nTicket: #$ticket\nResponse within 48 hours." : "✅ Demande reçue avec succès.\nTicket: #$ticket\nRéponse sous 48 heures.";
            $session->update(['status' => 'ACTIVE', 'otp' => null, 'identifier_input' => null]);
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        return $this->reply($session->phone_number, ($isEn ? "❌ Invalid OTP." : "❌ OTP invalide.") . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processTeacherLeaveType($session, $cmd) {
        $isEn = $session->locale === 'en';
        $types = ['1' => 'vacation', '2' => 'sick', '3' => 'personal', '4' => 'other'];
        if (!isset($types[$cmd])) return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
        
        $user = User::find($session->user_id);
        $ticket = 'REQ-' . strtoupper(Str::random(4));
        
        $leave = StaffLeave::create([
            'institution_id' => $session->institution_id,
            'staff_id' => optional($user->staff)->id ?? 0,
            'type' => $types[$cmd],
            'reason' => "Signalement initié via Chatbot. Ticket: $ticket",
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'status' => 'pending'
        ]);

        app(InAppNotificationService::class)->notifyStaffLeaveSubmitted($leave);
        
        $session->update(['status' => 'ACTIVE']);
        $msg = $isEn ? "✅ Request submitted. Ticket: #$ticket" : "✅ Requête transmise. Ticket: #$ticket";
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }


    // =================================================================================
    // --- 7. SHARED STUDENT UTILS (Finance, Report Cards, etc) ---
    // =================================================================================

    protected function processPaymentMenu($session, $student) {
        $isEn = $session->locale === 'en';
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, ($isEn ? "Not enrolled." : "Non inscrit.") . $this->getReturnPrompt($session), $session->institution_id);
        
        $fees = FeeStructure::where('grade_level_id', $enrollment->grade_level_id)->where('academic_session_id', $enrollment->academic_session_id)->where('institution_id', $session->institution_id)->where('payment_mode', 'global')->sum('amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)->where('academic_session_id', $enrollment->academic_session_id))->sum('amount');
        $due = $fees - $paid;
        
        $instId = $session->institution_id;
        $dueFmt = number_format($due, 2) . $this->currencySymbol($instId);
        $totalFmt = number_format($fees, 2) . $this->currencySymbol($instId);

        $msg = $isEn ? "💳 *Payment Details*\nTotal Fees: $totalFmt\nRemaining Due: $dueFmt\n\nChoose method:\n1️⃣ Credit/Debit Card\n2️⃣ Mobile Money" 
                     : "💳 *Détails de Paiement*\nFrais Totaux: $totalFmt\nReste à payer: $dueFmt\n\nChoisissez la méthode:\n1️⃣ Carte Bancaire\n2️⃣ Mobile Money";

        return $this->reply($session->phone_number, $msg, $session->institution_id);
    }

    protected function processPaymentMethod($session, $cmd) {
        $isEn = $session->locale === 'en';
        $institutionId = $session->institution_id;
        $studentId = $session->user_id;

        $unpaidInvoices = Invoice::where('student_id', $studentId)->whereIn('status', ['unpaid', 'partial', 'overdue'])->get();
        if ($unpaidInvoices->isEmpty()) {
            $session->update(['status' => 'ACTIVE']);
            $msg = $isEn ? "You have no pending invoices." : "Vous n'avez aucune facture en attente.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
        }

        $totalDue = $unpaidInvoices->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);
        $totalFmt = number_format($totalDue, 2) . " " . $this->currencySymbol($institutionId);

        if ($cmd == '1') {
            $token = base64_encode('checkout-' . $studentId . '-' . time());
            $link = route('login') . "?ref=" . $token; 
            $session->update(['status' => 'ACTIVE']);
            $msg = $isEn ? "Secure payment link: $link\nTotal Due: $totalFmt" : "Lien de paiement sécurisé: $link\nTotal à payer: $totalFmt";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
        }
        if ($cmd == '2') {
            $session->update(['status' => 'ACTIVE']);
            $msg = $isEn ? "Please use our merchant code to pay via Mobile Money.\nAmount Due: $totalFmt" : "Veuillez utiliser notre code marchand pour payer par Mobile Money.\nMontant Dû: $totalFmt";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
        }
        return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $institutionId);
    }

    protected function getBalance($session, $student) {
        $isEn = $session->locale === 'en';
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, ($isEn ? "Not enrolled." : "Non inscrit.") . $this->getReturnPrompt($session), $session->institution_id);
        
        $fees = FeeStructure::where('grade_level_id', $enrollment->grade_level_id)->where('academic_session_id', $enrollment->academic_session_id)->where('institution_id', $session->institution_id)->where('payment_mode', 'global')->sum('amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)->where('academic_session_id', $enrollment->academic_session_id))->sum('amount');
        $due = $fees - $paid;
        
        $instId = $session->institution_id;
        $totalFmt = number_format($fees, 2) . $this->currencySymbol($instId);
        $paidFmt = number_format($paid, 2) . $this->currencySymbol($instId);
        $dueFmt = number_format($due, 2) . $this->currencySymbol($instId);

        $msg = $isEn ? "💰 *Financial Status*\nTotal Fees: $totalFmt\nPaid: $paidFmt\nOutstanding Due: *$dueFmt*" 
                     : "💰 *Statut Financier*\nFrais Totaux: $totalFmt\nPayé: $paidFmt\nReste à payer: *$dueFmt*";

        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getPaymentHistory($session, $student) {
        $isEn = $session->locale === 'en';
        $payments = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
            ->latest()->take(3)->get();
            
        if ($payments->isEmpty()) {
            $msg = $isEn ? "No payment history found." : "Aucun paiement trouvé.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        
        $msg = $isEn ? "💵 *My Last Payments*\n" : "💵 *Mes Derniers Paiements*\n";
        foreach($payments as $p) {
            $msg .= "- " . number_format($p->amount, 2) . $this->currencySymbol($session->institution_id) . ($isEn ? " on " : " le ") . $p->payment_date->format('d/m/Y') . "\n";
        }
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getStudentTimetable($session, $student) {
        $isEn = $session->locale === 'en';
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, ($isEn ? "Not enrolled." : "Non inscrit.") . $this->getReturnPrompt($session), $session->institution_id);

        $today = strtolower(now()->format('l'));
        $routines = Timetable::with('subject')
            ->where('class_section_id', $enrollment->class_section_id)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        if ($routines->isNotEmpty()) {
            $reply = $isEn ? "📅 *Today's Timetable:*\n" : "📅 *Horaires d'aujourd'hui:*\n";
            foreach ($routines as $r) {
                $startTime = Carbon::parse($r->start_time)->format('h:i A');
                $subjectName = optional($r->subject)->name ?? 'Subject';
                $room = $r->room_number ? ($isEn ? " (Room: {$r->room_number})" : " (Salle: {$r->room_number})") : "";
                $reply .= "⏰ {$startTime} - {$subjectName}{$room}\n";
            }
        } else {
            $reply = $isEn ? "📅 *Today's Timetable:*\nNo classes scheduled for today." : "📅 *Horaires d'aujourd'hui:*\nAucun cours prévu pour aujourd'hui.";
        }
        
        return $this->reply($session->phone_number, $reply . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getStudentUpcomingExams($session, $student) {
        $isEn = $session->locale === 'en';
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, ($isEn ? "Not enrolled." : "Non inscrit.") . $this->getReturnPrompt($session), $session->institution_id);
        
        $exams = ExamSchedule::with('subject')
            ->where('class_section_id', $enrollment->class_section_id)
            ->where('exam_date', '>=', today())
            ->orderBy('exam_date')
            ->take(5)->get();
            
        if ($exams->isEmpty()) {
            $msg = $isEn ? "No upcoming exams scheduled." : "Aucun examen prévu prochainement.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        
        $msg = $isEn ? "📝 *Upcoming Exams*\n" : "📝 *Prochaines Épreuves*\n";
        foreach($exams as $e) {
            $date = Carbon::parse($e->exam_date)->format('d/m/Y');
            $time = $e->start_time ? Carbon::parse($e->start_time)->format('H:i') : '';
            $subjectName = optional($e->subject)->name ?? 'Subject';
            $msg .= "- {$subjectName} : {$date} {$time}\n";
        }
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getReportCard($session, $student) {
        $institutionId = $session->institution_id;
        $isEn = $session->locale === 'en';

        try {
            $isBlocked = InstitutionSetting::where('institution_id', $institutionId)->where('key', 'block_reports_on_debt')->value('value');

            if ($isBlocked == '1') {
                $unpaid = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total_amount - paid_amount'));

                if ($unpaid > 0) {
                    $currency = $this->currencySymbol($institutionId);
                    $formattedDebt = number_format($unpaid, 2) . ' ' . $currency;
                    $msg = $isEn 
                        ? "⛔ Access denied. You have an outstanding balance of $formattedDebt. Please settle to view results."
                        : "⛔ Accès refusé. Vous avez un solde impayé de $formattedDebt. Veuillez régler pour voir vos résultats.";
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
                }
            }

            $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
            if(!$enrollment) return $this->reply($session->phone_number, ($isEn ? "Not enrolled." : "Non inscrit.") . $this->getReturnPrompt($session), $institutionId);

            $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
            if (!$currentSession) return $this->reply($session->phone_number, ($isEn ? "No active session." : "Aucune session active.") . $this->getReturnPrompt($session), $institutionId);

            $hasMarks = ExamRecord::where('student_id', $student->id)
                ->whereHas('exam', fn($q) => $q->where('academic_session_id', $currentSession->id))->exists();

            if (!$hasMarks) {
                $msg = $isEn ? "📭 No exam results are available for this session yet." : "📭 Aucun résultat n'est encore disponible pour cette session.";
                return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
            }

            $originalUser = Auth::user();
            $originalSession = session('active_institution_id');
            
            $superAdmin = User::role('Super Admin')->first();
            if ($superAdmin) Auth::login($superAdmin);
            session(['active_institution_id' => $institutionId]);

            $reportRequest = \Illuminate\Http\Request::create('/dummy', 'GET', [
                'student_id' => $student->id,
                'mode' => 'single',
                'trimester' => 1,
                'semester' => 1
            ]);

            $controller = app(\App\Http\Controllers\ReportController::class);
            $response = $controller->bulletin($reportRequest);
            
            if ($originalUser) Auth::login($originalUser); else Auth::logout();
            session(['active_institution_id' => $originalSession]);

            if ($response instanceof \Illuminate\Http\RedirectResponse || (isset($response->getData()['status']) && $response->getData()['status'] == 'error')) {
                return $this->reply($session->phone_number, ($isEn ? "Report not available yet." : "Le bulletin n'est pas encore disponible."), $institutionId);
            }

            $html = $response->render();
            $html = preg_replace('/<div class="print-controls".*?<\/div>/s', '', $html);

            $dompdfStyles = '
            <style>
                table { table-layout: fixed !important; width: 100% !important; }
                .summary-container { display: table !important; width: 100% !important; margin-top: 10px; border-top: 1.5px solid #000; border-collapse: collapse; page-break-inside: avoid; }
                .summary-row { display: table-row !important; width: 100% !important; }
                .summary-row .label, .summary-row .val { display: table-cell !important; padding: 4px 0 !important; vertical-align: middle !important; border-bottom: 1px solid #ddd; }
                .summary-row .label { width: 60% !important; text-align: left !important; font-weight: bold; color: #444; font-size: 10px; }
                .summary-row .val { width: 20% !important; text-align: center !important; font-weight: bold; font-size: 11px; color: #000; }
                .footer-wrapper { position: relative; height: 90px; margin-top: 25px; width: 100%; display: block; clear: both; page-break-inside: avoid; }
                .qr-code { position: absolute; left: 0; bottom: 0; width: 60px; height: 60px; }
                .signature-block { position: absolute; right: 0; bottom: 0; width: 150px; text-align: center; font-size: 10px; }
                th { border-bottom: 1px solid #002b80 !important; padding-bottom: 4px !important; }
            </style>
            ';
            $html = str_replace('</head>', $dompdfStyles . '</head>', $html);
            $html = preg_replace('/<\/table>\s*<div class="divider-bottom"><\/div>\s*<table>\s*<tbody>/i', '<tbody>', $html);

            $schoolName = strtoupper(\Illuminate\Support\Str::limit(optional($student->institution)->name ?? __('reports.direction') ?? 'DIRECTION', 14, ''));
            $fallbackStamp = '<div style="position: absolute; left: 50%; margin-left: -35px; bottom: 0px; width: 70px; height: 70px; border: 2px solid #2585c9; border-radius: 35px; text-align: center; color: #2585c9; font-family: Helvetica, Arial, sans-serif; z-index: 5; background-color: rgba(255,255,255,0.7); box-sizing: border-box;">
                <div style="margin-top: 18px; font-size: 11px; font-weight: bold; letter-spacing: 1px;">BULLETIN</div>
                <div style="border-top: 1px solid #2585c9; width: 50px; margin: 2px auto;"></div>
                <div style="font-size: 8px; font-weight: bold; margin-top: 2px; line-height: 1;">' . $schoolName . '</div>
            </div>';
            $html = preg_replace('/<div class="stamp-overlay".*?<\/div>/s', $fallbackStamp, $html);

            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
                      ->loadHTML($html)
                      ->setPaper('a4', 'portrait');
                      
            $pdfContent = $pdf->output();

            $filename = "Bulletin_{$student->admission_number}_" . time() . ".pdf";
            $path = "temp/{$filename}";
            
            if (!Storage::disk('public')->exists('temp')) Storage::disk('public')->makeDirectory('temp');
            Storage::disk('public')->put($path, $pdfContent);
            
            $downloadUrl = asset('storage/' . $path);
            $caption = $isEn ? "Here is your report card." : "Voici votre bulletin de notes.";
            
            return $this->replyWithFile($session->phone_number, $downloadUrl, $caption . $this->getReturnPrompt($session), $filename, $institutionId);

        } catch (\Exception $e) {
            if (isset($originalUser) && $originalUser) Auth::login($originalUser);
            if (isset($originalSession)) session(['active_institution_id' => $originalSession]);
            
            $msg = $isEn ? "An error occurred generating your report card." : "Une erreur s'est produite lors de la génération du bulletin.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
        }
    }

    protected function getHomework($session, $student) {
        $isEn = $session->locale === 'en';
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, ($isEn ? "Not enrolled." : "Non inscrit.") . $this->getReturnPrompt($session), $session->institution_id);
        
        $hw = Assignment::where('class_section_id', $enrollment->class_section_id)->where('deadline', '>=', now())->latest()->take(3)->get();
        if($hw->isEmpty()) {
            $msg = $isEn ? "No homework found." : "Aucun devoir trouvé.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        
        $list = "";
        foreach($hw as $h) {
            $subjectName = optional($h->subject)->name ?? 'Subject';
            $list .= "📚 " . $subjectName . ": " . $h->title . " (" . $h->deadline->format('d/m') . ")\n";
        }
        
        return $this->reply($session->phone_number, ($isEn ? "📝 *Assignments:*\n" : "📝 *Devoirs:*\n") . $list . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getMiscFees($session, $student) {
        $isEn = $session->locale === 'en';
        $fees = FeeStructure::where('institution_id', $session->institution_id)->where('frequency', 'one_time')->get();
        if($fees->isEmpty()) {
            $msg = $isEn ? "No miscellaneous fees found." : "Aucun frais divers trouvé.";
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        
        $list = $fees->map(fn($f) => "- {$f->name}: {$f->amount} " . $this->currencySymbol($session->institution_id))->join("\n");
        return $this->reply($session->phone_number, ($isEn ? "📋 *Miscellaneous Fees:*\n" : "📋 *Frais Connexes:*\n") . $list . $this->getReturnPrompt($session), $session->institution_id);
    }
    
    protected function processQrOtpConfirm($session, $cmd) {
        $isEn = $session->locale === 'en';
        if ($cmd == '1') {
            $otp = rand(100000, 999999);
            $session->update(['otp' => $otp, 'status' => 'QR_OTP_INPUT']);
            
            $student = Student::find($session->user_id);
            
            if (!$this->authorizeChatbotInteraction($session->institution_id, 'sms')) {
                return $this->reply($session->phone_number, $isEn ? "⚠️ Cannot send OTP: Insufficient SMS credits." : "⚠️ Envoi impossible: crédits SMS épuisés.", $session->institution_id);
            }
            
            $this->notificationService->sendOtpNotification($student, $otp);
            return $this->reply($session->phone_number, $isEn ? "An OTP has been sent." : "Un code a été envoyé.", $session->institution_id);
        }
        return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processQrOtpInput($session, $text) {
        $isEn = $session->locale === 'en';
        $cleanInput = preg_replace('/[^0-9]/', '', $text);
        
        if ($cleanInput === (string)$session->otp) {
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
            $caption = ($isEn ? "Pickup QR for " : "QR de retrait pour ") . $student->first_name;
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            
            return $this->replyWithImage($session->phone_number, $qrUrl, $caption . $this->getReturnPrompt($session), $session->institution_id);
        }
        return $this->reply($session->phone_number, ($isEn ? "❌ Invalid OTP." : "❌ OTP invalide.") . $this->getReturnPrompt($session), $session->institution_id);
    }
    
    protected function processDerogation($s, $cmd) { 
        $isEn = $s->locale === 'en';
        $durations = ['1' => 3, '2' => 7, '3' => 10, '4' => 14];
        if (!isset($durations[$cmd])) return $this->reply($s->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($s), $s->institution_id);
        
        $days = $durations[$cmd];
        $student = Student::find($s->user_id);
        $ticket = 'DGR-' . strtoupper(Str::random(5));
        
        if($student) {
             $enrollment = $student->enrollments()->latest()->first();
             $created = StudentRequest::create([
                'institution_id' => $s->institution_id,
                'student_id' => $student->id,
                'academic_session_id' => optional($enrollment)->academic_session_id ?? null,
                'type' => 'fee_extension',
                'reason_key' => 'requests.reason_chatbot_fee_extension',
                'reason_params' => ['days' => $days],
                'reason_locale' => $s->locale ?? 'fr',
                'reason' => __('requests.reason_chatbot_fee_extension', ['days' => $days], $s->locale ?? 'fr'),
                'start_date' => now(),
                'end_date' => now()->addDays($days),
                'status' => 'submitted',
                'ticket_number' => $ticket,
                'created_by' => $s->user_id 
             ]);

             app(\App\Services\StudentRequestNotificationDispatcher::class)->onSubmitted($created);
        }

        $s->update(['status'=>'ACTIVE']); 
        $msg = $isEn ? "✅ Request for $days days submitted. Ticket: #$ticket" : "✅ Demande de dérogation pour $days jours soumise. Ticket: #$ticket";
        return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id); 
    }
    
    protected function processRequestType($session, $cmd) {
        $isEn = $session->locale === 'en';
        $validTypes = ['1' => 'early_exit', '2' => 'sick', '3' => 'late', '4' => 'absence'];
        
        if (!isset($validTypes[$cmd])) return $this->reply($session->phone_number, ($isEn ? "Invalid option." : "Option invalide.") . $this->getReturnPrompt($session), $session->institution_id);
        
        $session->update([
            'status' => 'REQUEST_REASON_SELECT',
            'identifier_input' => "REQ_TYPE:" . $validTypes[$cmd]
        ]);
        
        $msg = $isEn ? "Please provide a brief reason for this request:" : "Veuillez fournir un bref motif pour justifier la requête:";
        return $this->reply($session->phone_number, $msg, $session->institution_id);
    }
    
    protected function processRequestReason($session, $text) {
        $isEn = $session->locale === 'en';
        $type = explode(':', $session->identifier_input)[1] ?? 'other';
        $student = Student::find($session->user_id);
        $ticket = null;

        if($student) {
             $enrollment = $student->enrollments()->latest()->first();
             if($enrollment) {
                 $ticket = 'REQ-' . strtoupper(Str::random(8));
                 $created = StudentRequest::create([
                    'institution_id' => $session->institution_id,
                    'student_id' => $session->user_id,
                    'academic_session_id' => $enrollment->academic_session_id,
                    'type' => $type,
                    'reason' => $text,
                    'reason_locale' => $session->locale ?? app()->getLocale(),
                    'start_date' => now(), 
                    'status' => 'submitted',
                    'ticket_number' => $ticket,
                    'created_by' => $session->user_id
                 ]);

                 app(\App\Services\StudentRequestNotificationDispatcher::class)->onSubmitted($created);
             }
        }

        $session->update(['status' => 'ACTIVE']); 
        $msg = $isEn ? "✅ Request submitted successfully. Ticket: #$ticket" : "✅ Requête soumise avec succès. Ticket: #$ticket";
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id); 
    }

    // =================================================================================
    // --- 8. BASE UTILS & SCALABLE BILLING ENGINE ---
    // =================================================================================

    /**
     * Future-proof billing authorization for chatbot interactions.
     * Evaluates custom gateways, free tiers, subscriptions, and credit balances.
     */
    protected function authorizeChatbotInteraction($institutionId, $channel = 'whatsapp')
    {
        if (!$institutionId) {
            return true; 
        }

        $institution = Institution::find($institutionId);
        if (!$institution) return true;

        // RULE 1: Custom Third-Party Providers (BYO-Account)
        $hasCustomKey = InstitutionSetting::get($institutionId, "{$channel}_api_key") 
                     || InstitutionSetting::get($institutionId, "{$channel}_token");
        $gatewayMode = InstitutionSetting::get($institutionId, "{$channel}_gateway_mode", 'system');

        if ($hasCustomKey || strtolower($gatewayMode) === 'custom') {
            Log::info("Chatbot Billing: Bypassed. Institution uses their own {$channel} provider.");
            return true;
        }

        // RULE 2: Free Chatbot Tier / Waivers
        $isFree = InstitutionSetting::get($institutionId, 'chatbot_free_interactions', 0);
        if ($isFree == 1) {
            Log::info("Chatbot Billing: Bypassed. Institution has a free chatbot waiver.");
            return true;
        }

        // RULE 3: Separate Chatbot Quota (Future Evolution hook)
        $dedicatedChatbotCredits = InstitutionSetting::get($institutionId, 'chatbot_dedicated_credits');
        if ($dedicatedChatbotCredits !== null && is_numeric($dedicatedChatbotCredits)) {
            if ($dedicatedChatbotCredits > 0) {
                InstitutionSetting::set($institutionId, 'chatbot_dedicated_credits', $dedicatedChatbotCredits - 1);
                Log::info("Chatbot Billing: Deducted 1 Dedicated Credit. Remaining: " . ($dedicatedChatbotCredits - 1));
                return true;
            }
            Log::warning("Chatbot Billing: Out of Dedicated Chatbot Credits.", ['institution_id' => $institutionId]);
            return false;
        }

        // RULE 4: Standard Message Credit Deduction (Current System)
        $creditField = ($channel === 'sms') ? 'sms_credits' : 'whatsapp_credits';
        
        if ($institution->$creditField > 0) {
            $institution->decrement($creditField);
            Log::info("Chatbot Billing: Deducted 1 {$channel} credit. Remaining: " . ($institution->$creditField));
            return true;
        }

        Log::warning("Chatbot Billing: Institution out of {$channel} credits.", ['institution_id' => $institutionId]);
        return false;
    }

    protected function incrementAttempts($session, $msg)
    {
        $session->increment('attempts');
        if ($session->attempts >= 3) {
            Log::warning("Chatbot Flow: Max attempts reached. Terminating session.", ['phone' => $session->phone_number]);
            $session->delete();
            $isEn = $session->locale === 'en';
            return $this->reply($session->phone_number, $isEn ? "Too many failed attempts. Session closed." : "Trop d'échecs. Session fermée.", $session->institution_id);
        }
        return $this->reply($session->phone_number, $msg . " (" . $session->attempts . "/3)", $session->institution_id);
    }

    protected function reply($to, $message, $institutionId, $channel = 'whatsapp')
    {
        try {
            if (!$this->authorizeChatbotInteraction($institutionId, $channel)) {
                Log::error("Chatbot Delivery Aborted: Institution #{$institutionId} has exhausted their message credits.");
                return response()->json(['status' => 'error', 'message' => 'Insufficient credits']);
            }

            Log::info("Chatbot Webhook Target Reached - Sending Reply", ['to' => $to, 'institution_id' => $institutionId]);
            $this->notificationService->performSend($to, $message, $institutionId, false, $channel);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error("Chatbot Reply Delivery Error (Primary Attempt): " . $e->getMessage());
            
            if ($institutionId !== null) {
                try {
                    Log::warning("Chatbot Fallback: Attempting to send via Global Gateway.");
                    $this->notificationService->performSend($to, $message, null, false, $channel);
                    return response()->json(['status' => 'success', 'note' => 'Sent via global fallback']);
                } catch (\Exception $fallback_e) {
                    Log::error("Chatbot Reply Delivery Error (Fallback Attempt): " . $fallback_e->getMessage());
                }
            }
            return response()->json(['status' => 'error', 'message' => 'Failed to send reply']);
        }
    }

    protected function replyWithFile($to, $fileUrl, $caption, $filename, $institutionId, $channel = 'whatsapp') 
    {
        try {
            if (!$this->authorizeChatbotInteraction($institutionId, $channel)) {
                Log::error("Chatbot Delivery Aborted: Institution #{$institutionId} has exhausted their credits.");
                return response()->json(['status' => 'error', 'message' => 'Insufficient credits']);
            }
            $this->notificationService->performSendFile($to, $fileUrl, $caption, $filename, $institutionId);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
             Log::error("Chatbot File Delivery Error: " . $e->getMessage());
             return response()->json(['status' => 'error']);
        }
    }

    protected function replyWithImage($to, $imageUrl, $caption, $institutionId, $channel = 'whatsapp') 
    {
        try {
            if (!$this->authorizeChatbotInteraction($institutionId, $channel)) {
                Log::error("Chatbot Delivery Aborted: Institution #{$institutionId} has exhausted their credits.");
                return response()->json(['status' => 'error', 'message' => 'Insufficient credits']);
            }
            $this->notificationService->performSendImage($to, $imageUrl, $caption, $institutionId);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
             Log::error("Chatbot Image Delivery Error: " . $e->getMessage());
             return response()->json(['status' => 'error']);
        }
    }
}