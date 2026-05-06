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
     * Main Processor & Router
     */
    public function processMessage(array $data)
    {
        try {
            $phone = $data['from']; 
            $text = trim($data['body']);
            $phone = preg_replace('/[^0-9]/', '', $phone); 

            $session = ChatSession::where('phone_number', $phone)->first();

            // 1. Session Expiry Check
            if ($session && now()->gt($session->expires_at)) {
                $session->delete();
                $session = null;
                return $this->reply($phone, __('chatbot.session_ended') ?? "Session expirée. Veuillez envoyer 'Menu' pour recommencer.", null);
            }

            // 2. No Session -> Initialize
            if (!$session) {
                return $this->handleNewSession($phone, $text);
            }

            // 3. Keep-Alive & Locale
            $session->update(['last_interaction_at' => now(), 'expires_at' => now()->addMinutes(30)]);
            if ($session->locale) app()->setLocale($session->locale);

            // 4. Global Interceptor for "0" (Return to Menu)
            // Ensures "0" ALWAYS works, regardless of what state the user is currently stuck in
            if ($session->status !== 'AWAITING_ID' && $session->status !== 'AWAITING_OTP' && $session->status !== 'CHILD_SELECT') {
                if ($text === '0' || $text === '00' || strtolower($text) === 'menu') {
                    $session->update(['status' => 'ACTIVE', 'identifier_input' => null, 'otp' => null]); 
                    return $this->routeToMainMenu($session);
                }
            }

            // 5. State Machine Router
            switch ($session->status) {
                case 'AWAITING_ID':
                    return $this->processIdentity($session, $text);
                
                case 'AWAITING_OTP':
                    return $this->processOtp($session, $text);
                
                case 'CHILD_SELECT': // Multi-Child Selection for Parents
                    return $this->processChildSelection($session, $text);
                
                case 'ACTIVE':
                    return $this->processActiveMenu($session, $text);
                    
                // --- STUDENT SUB-FLOWS ---
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
                case 'STUDENT_SCHEDULE_SELECT':
                    return $this->processStudentScheduleSelect($session, $text);
                
                // --- LMD SUB-FLOWS ---
                case 'LMD_FEES_SELECT':
                    return $this->processLmdFees($session, $text);
                case 'LMD_RESULTS_SELECT':
                    return $this->processLmdResults($session, $text);

                // --- STAFF/HR SUB-FLOWS ---
                case 'TEACHER_ATTENDANCE_OTP':
                    return $this->processTeacherAttendanceOtp($session, $text);
                case 'TEACHER_SALARY_ADVANCE_PERCENTAGE':
                    return $this->processTeacherSalaryAdvancePercentage($session, $text);
                case 'TEACHER_SALARY_ADVANCE_OTP':
                    return $this->processTeacherSalaryAdvanceOtp($session, $text);
                case 'TEACHER_LEAVE_TYPE':
                    return $this->processTeacherLeaveType($session, $text);

                // --- HEADOFF / ADMIN SUB-FLOWS ---
                case 'HEADOFF_EFFECTIF_SELECT':
                    return $this->processHeadOffEffectif($session, $text);
                case 'HEADOFF_FINANCE_SELECT':
                    return $this->processHeadOffFinance($session, $text);
                case 'HEADOFF_BUDGET_SELECT':
                    return $this->processHeadOffBudget($session, $text);
                case 'ADMIN_RANKING_SELECT':
                    return $this->processAdminRanking($session, $text);
                case 'ADMIN_EXPORT_SELECT':
                    return $this->processAdminExport($session, $text);

                default:
                    // Self-heal: Reset to ACTIVE if state is unknown
                    $session->update(['status' => 'ACTIVE']); 
                    return $this->routeToMainMenu($session);
            }

        } catch (\Throwable $e) {
            Log::error("Chatbot Critical Error: " . $e->getMessage() . ' Line: ' . $e->getLine());
            if (isset($data['from'])) {
                $p = preg_replace('/[^0-9]/', '', $data['from']);
                return $this->reply($p, __('chatbot.system_error') ?? "Erreur système. Veuillez réessayer plus tard.", null);
            }
            return response()->json(['status' => 'error']);
        }
    }

    // =================================================================================
    // --- 1. INITIALIZATION & AUTHENTICATION ---
    // =================================================================================

    protected function handleNewSession($phone, $text)
    {
        $textLower = strtolower($text);
        
        // Check for specific administrative wakeup keywords
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
            return $this->reply($phone, __('chatbot.admin_welcome_prompt') ?? "Bienvenue, veuillez entrer votre identifiant (Shortcode).", null);
        }

        // Student / Parent Flow based on dynamic school keywords
        $keyword = ChatbotKeyword::where('keyword', $textLower)->first();
        
        if ($keyword || in_array($textLower, ['bonjour', 'hello', 'menu', 'start', 'salut', 'hi', 'portail'])) {
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

            $msg = $keyword->welcome_message ?? __('chatbot.welcome_message') ?? "Bienvenue! Veuillez entrer votre Matricule (Elève) ou votre numéro de téléphone (Parent).";
            return $this->reply($phone, $msg, $keyword->institution_id ?? null);
        }

        return $this->reply($phone, __('chatbot.keywords_not_found') ?? "Mot clé introuvable. Envoyez 'Bonjour' ou 'Hello' pour commencer.", null);
    }

    protected function processIdentity($session, $input)
    {
        // --- 1. STAFF AUTHENTICATION ---
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
            return $this->incrementAttempts($session, __('chatbot.admin_id_invalid') ?? "Identifiant invalide.");
        }

        // --- 2. PARENT AUTHENTICATION (Multiple Children Flow) ---
        // Clean the input phone number for reliable matching
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

        // --- 3. SINGLE STUDENT AUTHENTICATION ---
        $student = Student::with('parent')->where('admission_number', $input)->first();
        if ($student) {
            return $this->sendOtp($session, $student, 'student');
        }

        return $this->incrementAttempts($session, __('chatbot.student_id_invalid') ?? "Matricule ou Numéro de téléphone introuvable.");
    }

    protected function sendOtp($session, $model, $type)
    {
        $otp = rand(100000, 999999);
        $phone = null;
        
        if ($type === 'student') {
            $phone = $model->parent->father_phone ?? $model->parent->mother_phone ?? $model->parent->guardian_phone ?? $model->mobile_number;
        } elseif ($type === 'parent') {
            $phone = $model->father_phone ?? $model->mother_phone ?? $model->guardian_phone;
        } else {
            $phone = $model->phone; // Staff User
        }

        if (!$phone) return $this->reply($session->phone_number, __('chatbot.no_registered_phone') ?? "Aucun téléphone enregistré pour l'envoi de l'OTP.", $session->institution_id);
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $session->update([
            'user_id' => $model->id,
            'user_type' => $type === 'parent' ? 'parent' : $session->user_type, 
            'institution_id' => $model->institution_id ?? $model->institute_id,
            'otp' => $otp,
            'status' => 'AWAITING_OTP',
            'identifier_input' => $type 
        ]);

        $this->notificationService->performSend($cleanPhone, __('chatbot.otp_sms_message', ['otp' => $otp]) ?? "Votre code OTP Digitex est: $otp", $session->institution_id, true, 'sms');
        
        $masked = Str::mask($cleanPhone, '*', 3, -3);
        return $this->reply($session->phone_number, __('chatbot.otp_sent_notification', ['phone' => $masked]) ?? "OTP envoyé au $masked.", $session->institution_id);
    }

    protected function processOtp($session, $input)
    {
        if (trim($input) == $session->otp) {
            
            // If it's a parent login, route to Child Selection
            if ($session->user_type === 'parent') {
                $session->update(['status' => 'CHILD_SELECT', 'otp' => null]);
                return $this->processChildSelection($session, null); // Render the list
            }

            // Normal Student/Staff login
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            return $this->routeToMainMenu($session);
        }
        return $this->incrementAttempts($session, __('chatbot.invalid_otp') ?? "Code OTP invalide.");
    }

    // =================================================================================
    // --- 2. MULTI-CHILD SELECTION (PARENT V2 FLOW) ---
    // =================================================================================

    protected function processChildSelection($session, $text)
    {
        $parent = StudentParent::with('students.institution')->find($session->user_id);
        if (!$parent || $parent->students->isEmpty()) {
            $session->delete();
            return $this->reply($session->phone_number, "Erreur: Aucun enfant n'est lié à ce compte parent.", $session->institution_id);
        }

        $children = $parent->students;

        // If the parent hasn't replied yet, just render the list
        if (is_null($text)) {
            $msg = "👨‍👩‍👧 *Mes Enfants (My Children)*\n\nVeuillez sélectionner le dossier d'un élève en répondant par le numéro correspondant:\n\n";
            foreach ($children as $index => $child) {
                $school = $child->institution->name ?? 'School';
                $msg .= ($index + 1) . "️⃣ " . $child->full_name . " (" . $school . ")\n";
            }
            return $this->reply($session->phone_number, $msg, $session->institution_id);
        }

        // Parent replied with a number. Validate it.
        $selectedIndex = ((int)$text) - 1;
        if (isset($children[$selectedIndex])) {
            $selectedChild = $children[$selectedIndex];
            
            // Switch session identity to the selected child!
            $session->update([
                'user_id' => $selectedChild->id,
                'user_type' => 'student',
                'institution_id' => $selectedChild->institution_id,
                'status' => 'ACTIVE'
            ]);

            return $this->routeToMainMenu($session);
        }

        return $this->reply($session->phone_number, "❌ Option invalide. Veuillez sélectionner un chiffre de la liste.", $session->institution_id);
    }

    // =================================================================================
    // --- 3. MASTER MENU ROUTER ---
    // =================================================================================

    protected function routeToMainMenu($session)
    {
        if ($session->user_type === 'student') {
            $student = Student::with('institution')->find($session->user_id);
            $type = $student->institution->type ?? 'primary';
            
            // Check if University/LMD
            if (in_array($type, ['university', 'lmd'])) {
                return $this->sendLmdMenu($session);
            }
            return $this->sendStudentMenu($session);
        } 
        
        return $this->sendStaffMenu($session);
    }

    protected function processActiveMenu($session, $text)
    {
        // Language Switcher (Universal)
        if ($text === '99') {
            $newLocale = app()->getLocale() === 'en' ? 'fr' : 'en';
            $session->update(['locale' => $newLocale]);
            app()->setLocale($newLocale);
            $msg = $newLocale === 'fr' ? "✅ Langue changée en Français." : "✅ Language changed to English.";
            $this->reply($session->phone_number, $msg, $session->institution_id);
            return $this->routeToMainMenu($session);
        }

        // Logout (Universal)
        if (in_array(strtolower(trim($text)), ['logout', 'quitter', 'exit'])) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.logout_success') ?? "👋 Session fermée.", $session->institution_id);
        }

        // Dispatch based on Role
        if ($session->user_type === 'student') {
            $student = Student::with('institution')->find($session->user_id);
            $type = $student->institution->type ?? 'primary';
            if (in_array($type, ['university', 'lmd'])) {
                return $this->processLmdMenu($session, $text);
            }
            return $this->processStudentMenu($session, $text);
        }
        
        return $this->processStaffMenu($session, $text);
    }

    protected function getReturnPrompt($session) {
        $msg = $session->locale === 'fr' ? "Retour au Menu" : "Return to Menu";
        return "\n\n👉 0️⃣ " . $msg;
    }

    // =================================================================================
    // --- 4. PRIMARY / SECONDARY STUDENT (V2) ---
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

        $baseMenu = "🎓 *Portail Parents / Élèves*\n🏫 {$student->institution->name}\n👤 {$student->full_name}\n📘 {$info}\n\n";
        $baseMenu .= "Veuillez choisir une option:\n\n";
        $baseMenu .= "1️⃣ e-TD/e-Devoir\n";
        $baseMenu .= "2️⃣ Connaitre les frais (Balance)\n";
        $baseMenu .= "3️⃣ Mes Paiements\n";
        $baseMenu .= "4️⃣ Dérogation\n";
        $baseMenu .= "5️⃣ Mes requêtes\n";
        $baseMenu .= "6️⃣ Horaires (Cours & Examens)\n";
        $baseMenu .= "7️⃣ e-Bulletin\n";
        $baseMenu .= "8️⃣ QR Code Retrait enfant";

        return $baseMenu . "\n\n9️⃣9️⃣ 🌐 Language / Langue\n🚪 Envoyer *logout* pour quitter";
    }

    protected function sendStudentMenu($session) {
        return $this->reply($session->phone_number, $this->getMenuText($session), $session->institution_id);
    }

    protected function processStudentMenu($session, $text)
    {
        $student = Student::find($session->user_id);
        
        switch ($text) {
            case '1': return $this->getHomework($session, $student);
            case '2': return $this->getBalance($session, $student);
            case '3': return $this->getPaymentHistory($session, $student);
            case '4': 
                $session->update(['status' => 'DEROGATION_DURATION_SELECT']); 
                return $this->reply($session->phone_number, "⏳ *Demande de Dérogation*\nChoisissez la durée:\n1️⃣ 3 Jours\n2️⃣ 7 Jours\n3️⃣ 10 Jours\n4️⃣ 14 Jours" . $this->getReturnPrompt($session), $session->institution_id);
            case '5': 
                $session->update(['status' => 'REQUEST_TYPE_SELECT']); 
                return $this->reply($session->phone_number, "📨 *Mes Requêtes*\n1️⃣ Sortie Anticipée\n2️⃣ Maladie / Hôpital\n3️⃣ Retard\n4️⃣ Absence" . $this->getReturnPrompt($session), $session->institution_id);
            case '6': 
                $session->update(['status' => 'STUDENT_SCHEDULE_SELECT']);
                return $this->reply($session->phone_number, "📅 *Horaires*\n1️⃣ Cours (Aujourd'hui)\n2️⃣ Epreuves/Examen (A venir)" . $this->getReturnPrompt($session), $session->institution_id);
            case '7': 
                return $this->getReportCard($session, $student); 
            case '8': 
                $session->update(['status' => 'QR_OTP_CONFIRM']); 
                return $this->reply($session->phone_number, "🔐 *Générer un QR Code de Retrait*\n1️⃣ Demander un code de sécurité OTP" . $this->getReturnPrompt($session), $session->institution_id);
            default: 
                return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
        }
    }

    protected function processStudentScheduleSelect($session, $text) {
        if ($text == '0') { $session->update(['status' => 'ACTIVE']); return $this->sendStudentMenu($session); }
        
        $student = Student::find($session->user_id);
        if ($text == '1') {
            return $this->getStudentTimetable($session, $student);
        } elseif ($text == '2') {
            return $this->getStudentUpcomingExams($session, $student);
        }
        
        return $this->reply($session->phone_number, "Option invalide." . $this->getReturnPrompt($session), $session->institution_id);
    }

    // =================================================================================
    // --- 5. UNIVERSITY / LMD STUDENT (V2) ---
    // =================================================================================

    protected function getLmdMenuText($session) {
        $student = Student::find($session->user_id);
        $school = $student->institution->name ?? 'Université';
        $name = $student->full_name;

        return "🎓 *Portail Etudiant (LMD)*\n🏫 {$school}\n👤 {$name}\n\n" .
               "Veuillez choisir une option:\n\n" .
               "1️⃣ Frais Académiques\n" .
               "2️⃣ Horaires (Cours & Examens)\n" .
               "3️⃣ Résultats Académiques\n" .
               "4️⃣ Travaux Académiques (TP/Devoirs)\n" .
               "\n9️⃣9️⃣ 🌐 Language / Langue\n🚪 Envoyer *logout* pour quitter";
    }

    protected function sendLmdMenu($session) {
        return $this->reply($session->phone_number, $this->getLmdMenuText($session), $session->institution_id);
    }

    protected function processLmdMenu($session, $text)
    {
        $student = Student::find($session->user_id);
        
        switch ($text) {
            case '1': 
                $session->update(['status' => 'LMD_FEES_SELECT']);
                return $this->reply($session->phone_number, "💰 *Frais Académiques*\n1️⃣ Minerval\n2️⃣ Enrôlement\n3️⃣ Autres Frais\n4️⃣ Mes Paiements" . $this->getReturnPrompt($session), $session->institution_id);
            case '2': 
                $session->update(['status' => 'STUDENT_SCHEDULE_SELECT']);
                return $this->reply($session->phone_number, "📅 *Horaires*\n1️⃣ Cours (Aujourd'hui)\n2️⃣ Examens / Épreuves" . $this->getReturnPrompt($session), $session->institution_id);
            case '3': 
                $session->update(['status' => 'LMD_RESULTS_SELECT']);
                return $this->reply($session->phone_number, "📊 *Résultats Académiques*\n1️⃣ Semestre I\n2️⃣ Semestre II\n3️⃣ Moyenne & Transcript Global" . $this->getReturnPrompt($session), $session->institution_id);
            case '4': 
                return $this->getHomework($session, $student);
            default: 
                return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
        }
    }

    protected function processLmdFees($session, $text) {
        $student = Student::find($session->user_id);
        if ($text == '1' || $text == '2' || $text == '3') {
            return $this->getBalance($session, $student); 
        } elseif ($text == '4') {
            return $this->getPaymentHistory($session, $student);
        }
        return $this->reply($session->phone_number, "Option invalide." . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processLmdResults($session, $text) {
        $student = Student::find($session->user_id);
        
        $isBlocked = InstitutionSetting::where('institution_id', $session->institution_id)->where('key', 'block_reports_on_debt')->value('value');
        if ($isBlocked == '1') {
            $unpaid = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total_amount - paid_amount'));
            if ($unpaid > 0) {
                $currency = CurrencySymbol::default();
                return $this->reply($session->phone_number, "⛔ Accès refusé. Vous avez un solde impayé de " . number_format($unpaid, 2) . $currency . ". Veuillez régler pour voir vos résultats." . $this->getReturnPrompt($session), $session->institution_id);
            }
        }

        $downloadUrl = URL::signedRoute('reports.transcript', ['student_id' => $student->id], now()->addMinutes(30));
        $msg = "📊 *Vos Résultats LMD (Crédits & Semestres)*\n\nPour voir le détail de vos cours validés, non validés, et vos moyennes, veuillez télécharger votre relevé officiel :\n\n👉 $downloadUrl";
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }


    // =================================================================================
    // --- 6. STAFF / HR / ADMIN (V2) ---
    // =================================================================================

    protected function getStaffMenuText($session) {
        $user = User::find($session->user_id);
        $role = $session->user_type; 
        
        $menu = "👤 *Bienvenue, " . $user->name . "*\n\n";

        if ($role === 'super_admin') {
            $menu .= "🛠 *Menu Super Admin*\n\n1️⃣ Finance Globale\n2️⃣ Subscriptions\n3️⃣ Ecoles\n4️⃣ Crédits";
        } 
        elseif ($role === 'head_officer') {
            $menu .= "🏢 *Menu Direction Générale*\n\n" .
                     "1️⃣ Effectifs (Global & Par Ecole)\n" .
                     "2️⃣ Paiement Frais (Caisses du jour)\n" .
                     "3️⃣ Budget & Finance\n" .
                     "4️⃣ Classements (Ecoles)";
        }
        elseif ($role === 'school_admin') {
            $menu .= "🏫 *Menu Directeur*\n\n" .
                     "1️⃣ Effectif Global & Par Classe\n" .
                     "2️⃣ Etat Caisse & Prévision\n" .
                     "3️⃣ Elèves Débiteurs\n" .
                     "4️⃣ Présences du Jour";
        } 
        elseif ($role === 'teacher') {
            $menu .= "📚 *Menu Enseignant / Agent*\n\n" .
                     "1️⃣ Pointer présence (QR)\n" .
                     "2️⃣ Mes Horaires\n" .
                     "3️⃣ Mes Epreuves\n" .
                     "4️⃣ Mes Requêtes (Congé/Maladie)\n" .
                     "5️⃣ Avance sur Salaire";
        }

        $menu .= "\n\n9️⃣9️⃣ 🌐 Language / Langue\n🚪 Envoyer *logout* pour quitter";
        return $menu;
    }

    protected function sendStaffMenu($session) {
        return $this->reply($session->phone_number, $this->getStaffMenuText($session), $session->institution_id);
    }

    protected function processStaffMenu($session, $text)
    {
        $role = $session->user_type;
        $user = User::find($session->user_id);

        // --- HEAD OFFICER V2 LOGIC ---
        if ($role === 'head_officer') {
            switch($text) {
                case '1': 
                    $session->update(['status' => 'HEADOFF_EFFECTIF_SELECT']);
                    return $this->reply($session->phone_number, "📊 *Effectifs*\n1️⃣ Global\n2️⃣ Par Ecoles / Classes" . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $session->update(['status' => 'HEADOFF_FINANCE_SELECT']);
                    return $this->reply($session->phone_number, "💰 *Paiement Frais*\n1️⃣ Global & Prévision\n2️⃣ Etat caisses du jour\n3️⃣ Elèves débiteurs" . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $session->update(['status' => 'HEADOFF_BUDGET_SELECT']);
                    return $this->reply($session->phone_number, "🏦 *Budget & Finance*\n1️⃣ Budget global\n2️⃣ Budget par école\n3️⃣ Dépense globale\n4️⃣ Dépense par école\n5️⃣ Demandes de fonds encours" . $this->getReturnPrompt($session), $session->institution_id);
                case '4': 
                    $session->update(['status' => 'ADMIN_RANKING_SELECT']); 
                    return $this->reply($session->phone_number, "🏆 *Classements*\n1️⃣ Par Effectif\n2️⃣ Par Paiements" . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
            }
        }
        
        // --- DIRECTEUR V2 LOGIC ---
        elseif ($role === 'school_admin') {
            switch($text) {
                case '1': 
                    $students = Student::where('institution_id', $session->institution_id)->count();
                    $staff = \App\Models\Staff::where('institution_id', $session->institution_id)->count();
                    return $this->reply($session->phone_number, "👥 *Effectif Global*\nElèves: $students\nEnseignants/Staff: $staff" . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $todayCash = Payment::where('institution_id', $session->institution_id)->whereDate('payment_date', today())->sum('amount');
                    return $this->reply($session->phone_number, "💵 *Etat Caisse du Jour*\nRecettes du jour: " . number_format($todayCash, 2) . " " . CurrencySymbol::default() . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    $debtors = Invoice::where('institution_id', $session->institution_id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->distinct('student_id')->count('student_id');
                    return $this->reply($session->phone_number, "🚨 *Elèves Débiteurs*\nNombre d'élèves avec factures impayées: $debtors" . $this->getReturnPrompt($session), $session->institution_id);
                case '4': 
                    $present = StudentAttendance::where('institution_id', $session->institution_id)->whereDate('attendance_date', today())->where('status', 'present')->count();
                    return $this->reply($session->phone_number, "✅ *Présences du Jour*\nElèves présents aujourd'hui: $present" . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
            }
        }
        
        // --- TEACHER V2 LOGIC ---
        elseif ($role === 'teacher') {
            $staffId = $user->staff->id ?? 0;
            switch($text) {
                case '1': 
                    $otp = rand(100000, 999999);
                    $session->update(['status' => 'TEACHER_ATTENDANCE_OTP', 'otp' => $otp]);
                    $this->notificationService->performSend($user->phone ?? $session->phone_number, "Votre code OTP pour pointer la présence est : $otp", $session->institution_id, true, 'sms');
                    return $this->reply($session->phone_number, "🔒 Veuillez saisir le code OTP envoyé par SMS pour valider votre présence d'aujourd'hui." . $this->getReturnPrompt($session), $session->institution_id);
                case '2': 
                    $today = strtolower(now()->format('l'));
                    $tt = Timetable::with(['subject', 'classSection'])->where('teacher_id', $staffId)->where('day_of_week', $today)->orderBy('start_time')->get();
                    $msg = "📅 *Mes Horaires (Aujourd'hui)*\n\n";
                    $msg .= $tt->isEmpty() ? "Aucun cours." : $tt->map(fn($t) => "🕒 {$t->start_time->format('H:i')} - {$t->end_time->format('H:i')}\n📖 {$t->subject->name} ({$t->classSection->name})")->join("\n\n");
                    return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
                case '3': 
                    return $this->getTeacherExams($session, $staffId);
                case '4':
                    $session->update(['status' => 'TEACHER_LEAVE_TYPE']);
                    return $this->reply($session->phone_number, "📝 *Mes Requêtes*\n1️⃣ Demande de congé\n2️⃣ Signaler maladie\n3️⃣ Empêchement\n4️⃣ Retard" . $this->getReturnPrompt($session), $session->institution_id);
                case '5':
                    $session->update(['status' => 'TEACHER_SALARY_ADVANCE_PERCENTAGE']);
                    return $this->reply($session->phone_number, "💸 *Avance sur salaire*\nChoisissez le pourcentage:\n1️⃣ 50%\n2️⃣ 30%\n3️⃣ 20%\n4️⃣ 10%" . $this->getReturnPrompt($session), $session->institution_id);
                default: 
                    return $this->reply($session->phone_number, __('chatbot.invalid_option') . $this->getReturnPrompt($session), $session->institution_id);
            }
        }

        return $this->reply($session->phone_number, __('chatbot.unknown_command'), $session->institution_id);
    }

    // --- HEADOFF SUBFLOWS ---
    protected function processHeadOffEffectif($s, $t) {
        if ($t == '1') {
            $count = Student::where('institution_id', $s->institution_id)->count();
            return $this->reply($s->phone_number, "👥 *Effectif Global*: $count élèves." . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '2') {
            $msg = "🏫 *Effectif Par Classe*\n";
            $classes = ClassSection::where('institution_id', $s->institution_id)->get();
            foreach($classes as $c) {
                $count = StudentEnrollment::where('class_section_id', $c->id)->where('status', 'active')->count();
                $msg .= "- {$c->name}: $count élèves\n";
            }
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        }
        return $this->reply($s->phone_number, "Option invalide." . $this->getReturnPrompt($s), $s->institution_id);
    }

    protected function processHeadOffFinance($s, $t) {
        if ($t == '1') {
            $expected = Invoice::where('institution_id', $s->institution_id)->sum('total_amount');
            $collected = Invoice::where('institution_id', $s->institution_id)->sum('paid_amount');
            $msg = "📊 *Global & Prévision*\n- Attendu: " . number_format($expected, 2) . "\n- Percu: " . number_format($collected, 2) . "\n- Reste: " . number_format($expected - $collected, 2);
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '2') {
            $todayCash = Payment::where('institution_id', $s->institution_id)->whereDate('payment_date', today())->sum('amount');
            return $this->reply($s->phone_number, "💵 *Caisse du Jour*: " . number_format($todayCash, 2) . CurrencySymbol::default() . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '3') {
            $debtors = Invoice::where('institution_id', $s->institution_id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->distinct('student_id')->count('student_id');
            return $this->reply($s->phone_number, "🚨 *Elèves Débiteurs*: $debtors élèves avec un solde impayé." . $this->getReturnPrompt($s), $s->institution_id);
        }
        return $this->reply($s->phone_number, "Option invalide." . $this->getReturnPrompt($s), $s->institution_id);
    }

    protected function processHeadOffBudget($s, $t) {
        $user = User::find($s->user_id);
        $schools = clone $user->institutes; 
        if (!$schools || $schools->isEmpty()) {
            $schools = Institution::where('id', $s->institution_id)->get();
        }

        if ($t == '1') {
            $budgets = Budget::whereIn('institution_id', $schools->pluck('id'))->sum('allocated_amount');
            return $this->reply($s->phone_number, "🏦 *Budget Global Alloué*: " . number_format($budgets, 2) . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '2') {
            $msg = "🏦 *Budget Par Ecole*\n";
            foreach($schools as $school) {
                $b = Budget::where('institution_id', $school->id)->sum('allocated_amount');
                $msg .= "- {$school->name}: " . number_format($b, 2) . "\n";
            }
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '3') {
            $spent = Budget::whereIn('institution_id', $schools->pluck('id'))->sum('spent_amount');
            return $this->reply($s->phone_number, "💸 *Dépense Globale*: " . number_format($spent, 2) . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '4') {
            $msg = "💸 *Dépense Par Ecole*\n";
            foreach($schools as $school) {
                $b = Budget::where('institution_id', $school->id)->sum('spent_amount');
                $msg .= "- {$school->name}: " . number_format($b, 2) . "\n";
            }
            return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $s->institution_id);
        } elseif ($t == '5') {
            $pendingReq = FundRequest::whereIn('institution_id', $schools->pluck('id'))->where('status', 'pending')->count();
            return $this->reply($s->phone_number, "⏳ *Demandes encours*: $pendingReq demandes de fonds en attente." . $this->getReturnPrompt($s), $s->institution_id);
        }
        return $this->reply($s->phone_number, "Option invalide." . $this->getReturnPrompt($s), $s->institution_id);
    }

    protected function processAdminRanking($s, $t) { 
        $institutionId = $s->institution_id;
        $classes = ClassSection::where('institution_id', $institutionId)->get();
        $ranking = [];
        
        foreach($classes as $class) {
            $studentIds = StudentEnrollment::where('class_section_id', $class->id)->where('status', 'active')->pluck('student_id');
            if($studentIds->isEmpty()) continue;
            
            if ($t == '2') { // Paiements
                $val = Payment::where('institution_id', $institutionId)->whereHas('invoice', fn($q) => $q->whereIn('student_id', $studentIds))->sum('amount');
            } else { // Effectif (1)
                $val = $studentIds->count();
            }
            $ranking[] = ['name' => $class->name, 'val' => $val];
        }
        
        usort($ranking, fn($a, $b) => $b['val'] <=> $a['val']);
        
        $msg = "🏆 *Classement des Classes*\n";
        foreach(array_slice($ranking, 0, 10) as $idx => $r) {
            $fmt = ($t == '2') ? number_format($r['val'], 2) . CurrencySymbol::default() : $r['val'] . " élèves";
            $msg .= ($idx + 1) . ". {$r['name']} - " . $fmt . "\n";
        }
        
        return $this->reply($s->phone_number, $msg . $this->getReturnPrompt($s), $institutionId); 
    }

    // --- TEACHER HR SUBFLOWS (V2) ---
    protected function processTeacherAttendanceOtp($session, $text) {
        if (trim($text) == $session->otp) {
            $user = User::find($session->user_id);
            StaffAttendance::updateOrCreate(
                [
                    'institution_id' => $session->institution_id,
                    'staff_id' => $user->staff->id ?? 0,
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
            return $this->reply($session->phone_number, "✅ Présence marquée avec succès pour aujourd'hui." . $this->getReturnPrompt($session), $session->institution_id);
        }
        return $this->reply($session->phone_number, "❌ OTP invalide." . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getTeacherExams($session, $staffId) {
        $tt_classes = Timetable::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
        $alloc_classes = ClassSubject::where('teacher_id', $staffId)->pluck('class_section_id')->toArray();
        $classIds = array_unique(array_merge($tt_classes, $alloc_classes));
        
        $exams = ExamSchedule::with(['subject', 'classSection'])
            ->whereIn('class_section_id', $classIds)
            ->where('exam_date', '>=', today())
            ->orderBy('exam_date')
            ->take(6)->get();
            
        if ($exams->isEmpty()) return $this->reply($session->phone_number, "Aucune épreuve prévue prochainement pour vos classes." . $this->getReturnPrompt($session), $session->institution_id);
        
        $msg = "📝 *Mes Prochaines Épreuves*\n";
        foreach($exams as $e) {
            $date = Carbon::parse($e->exam_date)->format('d/m');
            $msg .= "- {$e->classSection->name} | {$e->subject->name} : {$date}\n";
        }
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processTeacherSalaryAdvancePercentage($session, $text) {
        $percentages = ['1' => 50, '2' => 30, '3' => 20, '4' => 10];
        if (!isset($percentages[$text])) return $this->reply($session->phone_number, "Option invalide." . $this->getReturnPrompt($session), $session->institution_id);
        
        $otp = rand(100000, 999999);
        $user = User::find($session->user_id);
        
        $session->update(['status' => 'TEACHER_SALARY_ADVANCE_OTP', 'identifier_input' => $percentages[$text], 'otp' => $otp]);
        
        // Send OTP via SMS
        $this->notificationService->performSend($user->phone ?? $session->phone_number, "Votre code OTP pour l'avance sur salaire est : $otp", $session->institution_id, true, 'sms');
        
        return $this->reply($session->phone_number, "🔒 Un code OTP vous a été envoyé par SMS. Saisissez-le pour confirmer votre avance de {$percentages[$text]}%." . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processTeacherSalaryAdvanceOtp($session, $text) {
        if (trim($text) == $session->otp) {
            $user = User::find($session->user_id);
            $percentage = $session->identifier_input;
            $ticket = 'SAL-' . strtoupper(Str::random(4));
            
            StaffLeave::create([
                'institution_id' => $session->institution_id,
                'staff_id' => $user->staff->id ?? 0,
                'type' => 'other',
                'reason' => "Demande d'avance sur salaire de {$percentage}% (Soumis via Chatbot). Ticket: $ticket",
                'start_date' => now(),
                'status' => 'pending'
            ]);
            
            $msg = "✅ Demande reçue avec succès.\nTicket: #$ticket\nRéponse sous 48 heures.";
            $session->update(['status' => 'ACTIVE', 'otp' => null, 'identifier_input' => null]);
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
        }
        return $this->reply($session->phone_number, "❌ OTP invalide." . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function processTeacherLeaveType($session, $text) {
        $types = ['1' => 'vacation', '2' => 'sick', '3' => 'personal', '4' => 'other'];
        if (!isset($types[$text])) return $this->reply($session->phone_number, "Option invalide." . $this->getReturnPrompt($session), $session->institution_id);
        
        $user = User::find($session->user_id);
        $ticket = 'REQ-' . strtoupper(Str::random(4));
        
        StaffLeave::create([
            'institution_id' => $session->institution_id,
            'staff_id' => $user->staff->id ?? 0,
            'type' => $types[$text],
            'reason' => "Signalement initié via Chatbot. Ticket: $ticket",
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'status' => 'pending'
        ]);
        
        $session->update(['status' => 'ACTIVE']);
        return $this->reply($session->phone_number, "✅ Requête transmise. Ticket: #$ticket" . $this->getReturnPrompt($session), $session->institution_id);
    }


    // =================================================================================
    // --- 7. SHARED STUDENT UTILS (Finance, Report Cards, etc) ---
    // =================================================================================

    protected function processPaymentMenu($session, $student) {
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $session->institution_id);
        
        $fees = FeeStructure::where('grade_level_id', $enrollment->grade_level_id)->where('academic_session_id', $enrollment->academic_session_id)->where('institution_id', $session->institution_id)->where('payment_mode', 'global')->sum('amount');
        $paid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)->where('academic_session_id', $enrollment->academic_session_id))->sum('amount');
        $due = $fees - $paid;
        
        return $this->reply($session->phone_number, __('chatbot.payment_method_menu', ['due' => number_format($due, 2) . CurrencySymbol::default(), 'total' => number_format($fees, 2) . CurrencySymbol::default()]), $session->institution_id);
    }

    protected function processPaymentMethod($session, $text) {
        $institutionId = $session->institution_id;
        $studentId = $session->user_id;

        $unpaidInvoices = Invoice::where('student_id', $studentId)->whereIn('status', ['unpaid', 'partial', 'overdue'])->get();
        if ($unpaidInvoices->isEmpty()) {
            $session->update(['status' => 'ACTIVE']);
            return $this->reply($session->phone_number, "Vous n'avez aucune facture en attente." . $this->getReturnPrompt($session), $institutionId);
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

    protected function getPaymentHistory($session, $student) {
        $payments = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
            ->latest()->take(3)->get();
        if ($payments->isEmpty()) return $this->reply($session->phone_number, "Aucun paiement trouvé." . $this->getReturnPrompt($session), $session->institution_id);
        
        $msg = "💵 *Mes Derniers Paiements*\n";
        foreach($payments as $p) {
            $msg .= "- " . number_format($p->amount, 2) . CurrencySymbol::default() . " le " . $p->payment_date->format('d/m/Y') . "\n";
        }
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getStudentTimetable($session, $student) {
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $session->institution_id);

        $today = strtolower(now()->format('l'));
        $routines = Timetable::with('subject')
            ->where('class_section_id', $enrollment->class_section_id)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        if ($routines->isNotEmpty()) {
            $reply = "📅 *Horaires d'aujourd'hui:*\n";
            foreach ($routines as $r) {
                $startTime = Carbon::parse($r->start_time)->format('h:i A');
                $subjectName = $r->subject->name ?? 'Subject';
                $room = $r->room_number ? " (Salle: {$r->room_number})" : "";
                $reply .= "⏰ {$startTime} - {$subjectName}{$room}\n";
            }
        } else {
            $reply = "📅 *Horaires d'aujourd'hui:*\nAucun cours prévu pour aujourd'hui.";
        }
        
        return $this->reply($session->phone_number, $reply . $this->getReturnPrompt($session), $session->institution_id);
    }

    protected function getStudentUpcomingExams($session, $student) {
        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $session->institution_id);
        
        $exams = ExamSchedule::with('subject')
            ->where('class_section_id', $enrollment->class_section_id)
            ->where('exam_date', '>=', today())
            ->orderBy('exam_date')
            ->take(5)->get();
            
        if ($exams->isEmpty()) return $this->reply($session->phone_number, "Aucun examen prévu prochainement." . $this->getReturnPrompt($session), $session->institution_id);
        
        $msg = "📝 *Prochaines Épreuves*\n";
        foreach($exams as $e) {
            $date = Carbon::parse($e->exam_date)->format('d/m/Y');
            $time = $e->start_time ? Carbon::parse($e->start_time)->format('H:i') : '';
            $msg .= "- {$e->subject->name} : {$date} {$time}\n";
        }
        return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $session->institution_id);
    }

    // --- REPAIRED REPORT CARD LOGIC ---
    protected function getReportCard($session, $student) {
        $institutionId = $session->institution_id;

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

        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if(!$enrollment) return $this->reply($session->phone_number, __('chatbot.not_enrolled') . $this->getReturnPrompt($session), $institutionId);

        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        if (!$currentSession) return $this->reply($session->phone_number, __('chatbot.no_session') . $this->getReturnPrompt($session), $institutionId);

        $hasMarks = ExamRecord::where('student_id', $student->id)
            ->whereHas('exam', fn($q) => $q->where('academic_session_id', $currentSession->id))->exists();

        if (!$hasMarks) {
            $msg = __('chatbot.no_results_found');
            if ($msg === 'chatbot.no_results_found') {
                $msg = $session->locale === 'fr' ? "📭 Aucun résultat n'est encore disponible pour cette session." : "📭 No exam results are available for this session yet.";
            }
            return $this->reply($session->phone_number, $msg . $this->getReturnPrompt($session), $institutionId);
        }

        $downloadUrl = URL::signedRoute('reports.bulletin', [
            'student_id' => $student->id,
            'mode' => 'single',
            'report_scope' => 'trimester',
            'trimester' => 1
        ], now()->addMinutes(30));

        $filename = "Bulletin_{$student->admission_number}.pdf";

        if (request()->getHost() == '127.0.0.1' || request()->getHost() == 'localhost') {
            return $this->reply($session->phone_number, "📄 Voici le lien de votre bulletin : $downloadUrl \n" . $this->getReturnPrompt($session), $institutionId);
        }

        $caption = __('chatbot.result_found') ?? "Voici votre bulletin de notes.";
        $caption .= $this->getReturnPrompt($session);
        
        $this->notificationService->performSendFile($session->phone_number, $downloadUrl, $caption, $filename, $institutionId);
        return response()->json(['status' => 'success']);
    }

    protected function getHomework($session, $student) {
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
        if ($text == '1') {
            $otp = rand(100000, 999999);
            $session->update(['otp' => $otp, 'status' => 'QR_OTP_INPUT']);
            
            $student = Student::find($session->user_id);
            $this->notificationService->sendOtpNotification($student, $otp);
            
            return $this->reply($session->phone_number, __('chatbot.otp_sent') ?? "Un code a été envoyé.", $session->institution_id);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . __('chatbot.qr_verification'), $session->institution_id);
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
                __('chatbot.qr_caption', ['student' => $student->first_name]) . $this->getReturnPrompt($session), 
                $session->institution_id
            );
            
            $session->update(['status' => 'ACTIVE', 'otp' => null]);
            return response()->json(['status' => 'success']);
        }
        return $this->reply($session->phone_number, __('chatbot.invalid_otp') . $this->getReturnPrompt($session), $session->institution_id);
    }
    
    protected function processDerogation($s, $t) { 
        $durations = ['1' => 3, '2' => 7, '3' => 10, '4' => 14];
        if (!isset($durations[$t])) return $this->reply($s->phone_number, "Option invalide." . $this->getReturnPrompt($s), $s->institution_id);
        
        $days = $durations[$t];
        $student = Student::find($s->user_id);
        $ticket = 'DGR-' . strtoupper(Str::random(5));
        
        if($student) {
             $enrollment = $student->enrollments()->latest()->first();
             StudentRequest::create([
                'institution_id' => $s->institution_id,
                'student_id' => $student->id,
                'academic_session_id' => $enrollment->academic_session_id ?? 0,
                'type' => 'leave',
                'reason' => "Demande de dérogation parentale pour {$days} jours (Soumis via Chatbot).",
                'start_date' => now(),
                'end_date' => now()->addDays($days),
                'status' => 'pending',
                'ticket_number' => $ticket,
                'created_by' => $s->user_id 
             ]);
        }

        $s->update(['status'=>'ACTIVE']); 
        return $this->reply($s->phone_number, __('chatbot.derogation_submitted', ['days' => $days, 'ticket' => $ticket]) . $this->getReturnPrompt($s), $s->institution_id); 
    }
    
    protected function processRequestType($session, $text) {
        $validTypes = ['1' => 'early_exit', '2' => 'sick', '3' => 'late', '4' => 'absence'];
        
        if (!isset($validTypes[$text])) return $this->reply($session->phone_number, __('chatbot.invalid_option') . "\n\n" . __('chatbot.request_menu'), $session->institution_id);
        
        $session->update([
            'status' => 'REQUEST_REASON_SELECT',
            'identifier_input' => "REQ_TYPE:" . $validTypes[$text]
        ]);
        
        return $this->reply($session->phone_number, __('requests.chatbot_ask_reason') ?? "Veuillez fournir un bref motif pour justifier la requête.", $session->institution_id);
    }
    
    protected function processRequestReason($session, $text) {
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

    // =================================================================================
    // --- 8. BASE UTILS ---
    // =================================================================================

    protected function incrementAttempts($session, $msg)
    {
        $session->increment('attempts');
        if ($session->attempts >= 3) {
            $session->delete();
            return $this->reply($session->phone_number, __('chatbot.too_many_attempts') ?? "Trop d'échecs. Session fermée.", $session->institution_id);
        }
        return $this->reply($session->phone_number, $msg . " (" . $session->attempts . "/3)", $session->institution_id);
    }

    protected function reply($to, $message, $institutionId)
    {
        $this->notificationService->performSend($to, $message, $institutionId, false, 'whatsapp');
        return response()->json(['status' => 'success']);
    }
}