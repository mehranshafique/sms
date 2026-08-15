<?php

namespace App\Services\Voice;

use App\Models\AcademicSession;
use App\Models\FeeStructure;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Invoice;
use App\Models\Notice;
use App\Models\ParentMeeting;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentPickup;
use App\Models\StudentRequest;
use App\Services\CurrencyService;
use Illuminate\Support\Str;

class VoiceAnswerService
{
    public function __construct(
        protected CurrencyService $currency,
        protected VoiceIdentityService $identity
    ) {
    }

    public function guestInfo(int $institutionId, string $topic, string $locale): string
    {
        $isEn = $locale === 'en';
        $key = match ($topic) {
            'admission' => 'chatbot_admission_info',
            'fees' => 'chatbot_fees_info',
            default => 'chatbot_contact_info',
        };

        $custom = trim((string) InstitutionSetting::get($institutionId, $key, ''));
        if ($custom !== '') {
            return $this->spoken($custom);
        }

        $school = $this->identity->schoolName($institutionId);
        $phone = Institution::find($institutionId)?->phone
            ?: InstitutionSetting::get($institutionId, 'school_phone', '');

        return match ($topic) {
            'admission' => $isEn
                ? "For admission information at {$school}, please contact the school office or use WhatsApp text chat option 1 to pre-register."
                : "Pour les informations d'admission à {$school}, contactez le secrétariat ou utilisez le chat WhatsApp option 1 pour vous préinscrire.",
            'fees' => $isEn
                ? "School fees depend on the class. Contact the school office or log in to the parent portal for your child's balance."
                : "Les frais scolaires dépendent de la classe. Contactez le secrétariat ou connectez-vous au portail parent pour le solde de votre enfant.",
            default => $isEn
                ? ($phone
                    ? "You can reach {$school} at {$this->spokenPhone($phone)}."
                    : "Please contact the school office of {$school} during working hours.")
                : ($phone
                    ? "Vous pouvez joindre {$school} au {$this->spokenPhone($phone)}."
                    : "Veuillez contacter le secrétariat de {$school} pendant les heures ouvrables."),
        };
    }

    public function portalHelp(string $locale): string
    {
        return $locale === 'en'
            ? 'To open the parent portal, use the Digitex mobile app or WhatsApp text chat and choose parent login.'
            : 'Pour ouvrir le portail parent, utilisez l\'application Digitex ou le chat WhatsApp et choisissez la connexion parent.';
    }

    public function feeBalance(Student $student, int $institutionId, string $locale): string
    {
        $isEn = $locale === 'en';
        $name = $this->firstName($student);
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();

        if (! $enrollment) {
            return $isEn
                ? "{$name} is not currently enrolled."
                : "{$name} n'est pas actuellement inscrit.";
        }

        $fees = (float) FeeStructure::where('grade_level_id', $enrollment->grade_level_id)
            ->where('academic_session_id', $enrollment->academic_session_id)
            ->where('institution_id', $institutionId)
            ->where('payment_mode', 'global')
            ->sum('amount');

        $paid = (float) Payment::whereHas('invoice', fn ($q) => $q
            ->where('student_id', $student->id)
            ->where('academic_session_id', $enrollment->academic_session_id)
        )->sum('amount');

        $due = max(0, $fees - $paid);
        $dueSpoken = $this->spokenAmount($due, $institutionId, $locale);

        if ($due <= 0.009) {
            return $isEn
                ? "For {$name}, there is no remaining balance."
                : "Pour {$name}, il n'y a pas de solde restant.";
        }

        return $isEn
            ? "For {$name}, remaining balance is {$dueSpoken}."
            : "Pour {$name}, le solde restant est de {$dueSpoken}.";
    }

    public function todayAttendance(Student $student, string $locale): string
    {
        $isEn = $locale === 'en';
        $name = $this->firstName($student);
        $today = now()->toDateString();
        $row = StudentAttendance::where('student_id', $student->id)
            ->whereDate('attendance_date', $today)
            ->latest('id')
            ->first();

        if (! $row) {
            return $isEn
                ? "No attendance recorded today for {$name}."
                : "Aucune présence enregistrée aujourd'hui pour {$name}.";
        }

        $status = $this->attendanceLabel((string) $row->status, $locale);

        return $isEn
            ? "Today, {$name} is marked {$status}."
            : "Aujourd'hui, {$name} est marqué {$status}.";
    }

    public function latestNotice(int $institutionId, string $locale): string
    {
        $isEn = $locale === 'en';
        $notice = Notice::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('audience')
                    ->orWhereIn('audience', ['all', 'parent', 'student']);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        if (! $notice) {
            return $isEn
                ? 'There are no published notices at the moment.'
                : 'Il n\'y a aucun avis publié pour le moment.';
        }

        $date = $notice->published_at?->format('d/m/Y') ?? $notice->created_at?->format('d/m/Y');
        $title = $this->spoken((string) $notice->title);

        return $isEn
            ? "Latest notice from {$date}: {$title}."
            : "Dernier avis du {$date} : {$title}.";
    }

    public function latestPtm(Student $student, int $institutionId, string $locale): string
    {
        $isEn = $locale === 'en';
        $name = $this->firstName($student);

        $meeting = ParentMeeting::query()
            ->where('institution_id', $institutionId)
            ->where(function ($q) use ($student) {
                $q->where('student_id', $student->id)
                    ->orWhere(function ($cq) use ($student) {
                        $sectionId = $student->enrollments()
                            ->where('status', 'active')
                            ->latest()
                            ->value('class_section_id');
                        if ($sectionId) {
                            $cq->where('scope', 'class')->where('class_section_id', $sectionId);
                        } else {
                            $cq->whereRaw('1 = 0');
                        }
                    });
            })
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->orderByDesc('preferred_date')
            ->orderByDesc('id')
            ->first();

        if (! $meeting) {
            return $isEn
                ? "No parent teacher meeting is scheduled for {$name}."
                : "Aucune réunion parents-professeurs n'est planifiée pour {$name}.";
        }

        $date = $meeting->preferred_date?->format('d/m/Y') ?? '—';
        $topic = $this->spoken((string) ($meeting->topic ?: ($isEn ? 'general discussion' : 'discussion générale')));
        $status = $this->ptmStatusLabel((string) $meeting->status, $locale);

        return $isEn
            ? "Parent teacher meeting for {$name}: {$topic}, on {$date}, status {$status}."
            : "Réunion parents-professeurs pour {$name} : {$topic}, le {$date}, statut {$status}.";
    }

    /**
     * Phase 2: next due invoice and last payment, spoken in short sentences.
     */
    public function feeDetails(Student $student, int $institutionId, string $locale): string
    {
        $isEn = $locale === 'en';
        $name = $this->firstName($student);

        $nextInvoice = Invoice::where('institution_id', $institutionId)
            ->where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->first();

        $lastPayment = Payment::whereHas('invoice', fn ($q) => $q->where('student_id', $student->id))
            ->latest('id')
            ->first();

        $parts = [];

        if ($nextInvoice) {
            $remaining = max(0, (float) $nextInvoice->total_amount - (float) $nextInvoice->paid_amount);
            $amount = $this->spokenAmount($remaining, $institutionId, $locale);
            $due = $nextInvoice->due_date?->format('d/m/Y');

            $parts[] = $isEn
                ? ($due
                    ? "The next payment for {$name} is {$amount}, due on {$due}."
                    : "The next payment for {$name} is {$amount}.")
                : ($due
                    ? "Le prochain paiement pour {$name} est de {$amount}, à régler avant le {$due}."
                    : "Le prochain paiement pour {$name} est de {$amount}.");
        } else {
            $parts[] = $isEn
                ? "There is no pending invoice for {$name}."
                : "Il n'y a aucune facture en attente pour {$name}.";
        }

        if ($lastPayment) {
            $amount = $this->spokenAmount((float) $lastPayment->amount, $institutionId, $locale);
            $date = $lastPayment->created_at?->format('d/m/Y');

            $parts[] = $isEn
                ? "The last payment received was {$amount} on {$date}."
                : "Le dernier paiement reçu était de {$amount} le {$date}.";
        }

        return implode(' ', $parts);
    }

    /**
     * Phase 2: latest pickup (QR) request status for the selected child.
     */
    public function pickupStatus(Student $student, int $institutionId, string $locale): string
    {
        $isEn = $locale === 'en';
        $name = $this->firstName($student);

        $pickup = StudentPickup::where('institution_id', $institutionId)
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        if (! $pickup) {
            return $isEn
                ? "There is no pickup request for {$name}."
                : "Il n'y a aucune demande de sortie pour {$name}.";
        }

        $status = $this->pickupStatusLabel((string) $pickup->status, $locale);
        $date = $pickup->created_at?->format('d/m/Y H:i');

        if ($pickup->status === 'pending' && $pickup->expires_at && $pickup->expires_at->isPast()) {
            $status = $isEn ? 'expired' : 'expiré';
        }

        return $isEn
            ? "The latest pickup request for {$name} from {$date} is {$status}."
            : "La dernière demande de sortie pour {$name} du {$date} est {$status}.";
    }

    /**
     * Phase 2: latest student request (absence, fee extension, ...) status.
     */
    public function requestStatus(Student $student, int $institutionId, string $locale): string
    {
        $isEn = $locale === 'en';
        $name = $this->firstName($student);

        $request = StudentRequest::where('institution_id', $institutionId)
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        if (! $request) {
            return $isEn
                ? "There is no request on file for {$name}."
                : "Aucune demande enregistrée pour {$name}.";
        }

        app()->setLocale($locale);
        $type = $this->spoken($request->typeLabel());
        $status = $this->requestStatusLabel((string) $request->status, $locale);
        $ticket = $request->ticket_number ? $this->spokenReference((string) $request->ticket_number) : null;

        $text = $isEn
            ? "The latest request for {$name} is {$type}, status {$status}."
            : "La dernière demande pour {$name} est {$type}, statut {$status}.";

        if ($ticket) {
            $text .= $isEn
                ? " Ticket number {$ticket}."
                : " Numéro de ticket {$ticket}.";
        }

        return $text;
    }

    public function schoolContact(int $institutionId, string $locale): string
    {
        return $this->guestInfo($institutionId, 'contact', $locale);
    }

    public function secretaryMessage(int $institutionId, string $locale): string
    {
        $custom = trim((string) InstitutionSetting::get($institutionId, 'voice_ivr_secretary_message', ''));
        if ($custom !== '') {
            return $this->spoken($custom);
        }

        return $locale === 'en'
            ? 'Please contact the school secretary during office hours.'
            : 'Veuillez contacter le secrétariat de l\'école pendant les heures de bureau.';
    }

    protected function firstName(Student $student): string
    {
        $name = trim((string) ($student->first_name ?: $student->full_name ?: 'student'));

        return $this->spoken(Str::before($name, ' ') ?: $name);
    }

    protected function spokenAmount(float $amount, int $institutionId, string $locale): string
    {
        $settings = $this->currency->getSettings($institutionId);
        $rounded = round($amount, max(0, min(2, $settings['decimals'])));
        $number = number_format($rounded, $rounded == floor($rounded) ? 0 : 2, '.', '');
        $code = strtoupper($settings['code'] ?? 'USD');

        if ($locale === 'en') {
            $unit = match ($code) {
                'USD' => 'dollars',
                'EUR' => 'euros',
                'CDF' => 'Congolese francs',
                'XAF', 'XOF' => 'francs',
                default => strtolower($code),
            };

            return "{$number} {$unit}";
        }

        $unit = match ($code) {
            'USD' => 'dollars',
            'EUR' => 'euros',
            'CDF' => 'francs congolais',
            'XAF', 'XOF' => 'francs',
            default => strtolower($code),
        };

        return "{$number} {$unit}";
    }

    protected function spokenPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? $phone;

        return trim(chunk_split($digits, 1, ' '));
    }

    /** Space out references so TTS reads them character by character. */
    protected function spokenReference(string $reference): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $reference) ?? $reference;

        return trim(implode(' ', preg_split('//u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [$clean]));
    }

    protected function pickupStatusLabel(string $status, string $locale): string
    {
        $status = strtolower($status);

        if ($locale === 'en') {
            return match ($status) {
                'pending' => 'waiting for approval',
                'scanned' => 'scanned at the gate',
                'approved' => 'approved',
                'rejected' => 'rejected',
                'expired' => 'expired',
                default => $status,
            };
        }

        return match ($status) {
            'pending' => 'en attente de validation',
            'scanned' => 'scannée au portail',
            'approved' => 'approuvée',
            'rejected' => 'refusée',
            'expired' => 'expirée',
            default => $status,
        };
    }

    protected function requestStatusLabel(string $status, string $locale): string
    {
        $status = strtolower($status);

        if ($locale === 'en') {
            return match ($status) {
                'submitted', 'pending' => 'submitted',
                'under_review' => 'under review',
                'approved' => 'approved',
                'partially_approved' => 'partially approved',
                'rejected' => 'rejected',
                'additional_info_required' => 'waiting for more information',
                'honored' => 'completed',
                'expired' => 'expired',
                default => str_replace('_', ' ', $status),
            };
        }

        return match ($status) {
            'submitted', 'pending' => 'soumise',
            'under_review' => 'en cours d\'examen',
            'approved' => 'approuvée',
            'partially_approved' => 'partiellement approuvée',
            'rejected' => 'refusée',
            'additional_info_required' => 'en attente d\'informations',
            'honored' => 'traitée',
            'expired' => 'expirée',
            default => str_replace('_', ' ', $status),
        };
    }

    protected function spoken(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\*+/', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim(mb_substr($text, 0, 400));
    }

    protected function attendanceLabel(string $status, string $locale): string
    {
        $status = strtolower($status);
        $mapEn = [
            'present' => 'present',
            'absent' => 'absent',
            'late' => 'late',
            'excused' => 'excused',
        ];
        $mapFr = [
            'present' => 'présent',
            'absent' => 'absent',
            'late' => 'en retard',
            'excused' => 'excusé',
        ];

        return ($locale === 'en' ? $mapEn : $mapFr)[$status] ?? $status;
    }

    protected function ptmStatusLabel(string $status, string $locale): string
    {
        $status = strtolower($status);
        if ($locale === 'en') {
            return match ($status) {
                'pending' => 'pending',
                'confirmed' => 'confirmed',
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                default => $status,
            };
        }

        return match ($status) {
            'pending' => 'en attente',
            'confirmed' => 'confirmé',
            'completed' => 'terminé',
            'cancelled' => 'annulé',
            default => $status,
        };
    }
}
