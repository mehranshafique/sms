<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\DisciplinaryRecord;
use App\Models\ExamRecord;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReenrollmentCampaign;
use App\Models\ReenrollmentConfirmation;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentConductRecord;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReenrollmentService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    public function openCampaign(array $data, int $institutionId, ?int $userId = null): ReenrollmentCampaign
    {
        return DB::transaction(function () use ($data, $institutionId, $userId) {
            $campaign = ReenrollmentCampaign::create([
                'institution_id' => $institutionId,
                'name' => $data['name'],
                'from_academic_session_id' => $data['from_academic_session_id'],
                'to_academic_session_id' => $data['to_academic_session_id'],
                'min_fee_amount' => (float) ($data['min_fee_amount'] ?? 0),
                'opens_at' => $data['opens_at'] ?? now()->toDateString(),
                'closes_at' => $data['closes_at'] ?? null,
                'status' => ReenrollmentCampaign::STATUS_OPEN,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId ?? Auth::id(),
            ]);

            $this->seedConfirmations($campaign);

            return $campaign->fresh(['fromSession', 'toSession']);
        });
    }

    public function seedConfirmations(ReenrollmentCampaign $campaign): int
    {
        $enrollments = StudentEnrollment::with(['student', 'classSection'])
            ->where('institution_id', $campaign->institution_id)
            ->where('academic_session_id', $campaign->from_academic_session_id)
            ->whereIn('status', ['active', 'promoted'])
            ->whereHas('student', fn ($q) => $q->where('status', 'active'))
            ->get();

        $created = 0;
        foreach ($enrollments as $enrollment) {
            $exists = ReenrollmentConfirmation::where('campaign_id', $campaign->id)
                ->where('student_id', $enrollment->student_id)
                ->exists();
            if ($exists) {
                continue;
            }

            $paid = $this->computePaidAmount($campaign, (int) $enrollment->student_id);
            $required = (float) $campaign->min_fee_amount;

            ReenrollmentConfirmation::create([
                'institution_id' => $campaign->institution_id,
                'campaign_id' => $campaign->id,
                'student_id' => $enrollment->student_id,
                'from_enrollment_id' => $enrollment->id,
                'from_class_section_id' => $enrollment->class_section_id,
                'proposed_class_section_id' => null,
                'status' => ReenrollmentConfirmation::STATUS_PENDING,
                'amount_required' => $required,
                'amount_paid' => $paid,
                'payment_status' => $this->paymentStatusLabel($required, $paid),
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Send the "re-enrollment is open, please confirm" message to parents.
     * Only rows still awaiting a parent decision are contacted.
     *
     * @return array{sent: int, skipped: int}
     */
    public function sendInvitations(ReenrollmentCampaign $campaign, bool $isReminder = false, ?int $limit = null): array
    {
        $query = ReenrollmentConfirmation::with(['student.parent', 'student.institution', 'campaign.toSession', 'fromClassSection.gradeLevel'])
            ->where('campaign_id', $campaign->id)
            ->where('status', ReenrollmentConfirmation::STATUS_PENDING);

        if ($isReminder) {
            // Do not re-hit parents contacted in the last 24h.
            $query->where(function ($q) {
                $q->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', now()->subDay());
            });
        } else {
            $query->whereNull('invitation_sent_at');
        }

        if ($limit) {
            $query->limit($limit);
        }

        $sent = 0;
        $skipped = 0;

        foreach ($query->get() as $confirmation) {
            if (! $confirmation->student) {
                $skipped++;
                continue;
            }

            $this->notifyLifecycle(
                $confirmation,
                $isReminder ? 'reenrollment_reminder' : 'reenrollment_invitation'
            );

            $confirmation->forceFill($isReminder
                ? [
                    'last_reminder_at' => now(),
                    'reminder_count' => (int) $confirmation->reminder_count + 1,
                ]
                : [
                    'invitation_sent_at' => now(),
                    'last_reminder_at' => now(),
                ])->save();

            $sent++;
        }

        if (! $isReminder && $sent > 0) {
            $campaign->forceFill([
                'invitations_sent_at' => now(),
                'invitations_sent_count' => (int) $campaign->invitations_sent_count + $sent,
            ])->save();
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /** Send a single reminder for one record (admin action on the review screen). */
    public function sendReminder(ReenrollmentConfirmation $confirmation): void
    {
        $confirmation->loadMissing(['student.parent', 'student.institution', 'campaign.toSession', 'fromClassSection.gradeLevel']);

        $this->notifyLifecycle($confirmation, 'reenrollment_reminder');

        $confirmation->forceFill([
            'last_reminder_at' => now(),
            'reminder_count' => (int) $confirmation->reminder_count + 1,
        ])->save();
    }

    public function closeCampaign(ReenrollmentCampaign $campaign): ReenrollmentCampaign
    {
        return DB::transaction(function () use ($campaign) {
            $campaign->update([
                'status' => ReenrollmentCampaign::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            // Families who never answered are marked expired so the queue reads clean.
            ReenrollmentConfirmation::where('campaign_id', $campaign->id)
                ->where('status', ReenrollmentConfirmation::STATUS_PENDING)
                ->update(['status' => ReenrollmentConfirmation::STATUS_EXPIRED]);

            return $campaign->fresh();
        });
    }

    public function reopenCampaign(ReenrollmentCampaign $campaign): ReenrollmentCampaign
    {
        return DB::transaction(function () use ($campaign) {
            $campaign->update([
                'status' => ReenrollmentCampaign::STATUS_OPEN,
                'closed_at' => null,
                'closes_at' => $campaign->closes_at && $campaign->closes_at->isPast()
                    ? null
                    : $campaign->closes_at,
            ]);

            ReenrollmentConfirmation::where('campaign_id', $campaign->id)
                ->where('status', ReenrollmentConfirmation::STATUS_EXPIRED)
                ->update(['status' => ReenrollmentConfirmation::STATUS_PENDING]);

            return $campaign->fresh();
        });
    }

    /**
     * Put a declined / rejected record back in the parent's hands
     * (parent changed their mind, or the school reviewed a rejection).
     */
    public function reopenConfirmation(ReenrollmentConfirmation $confirmation, ?string $adminNote = null): ReenrollmentConfirmation
    {
        if ($confirmation->status === ReenrollmentConfirmation::STATUS_CONFIRMED) {
            throw new \RuntimeException(__('reenrollment.errors.already_confirmed'));
        }

        $confirmation->update([
            'status' => ReenrollmentConfirmation::STATUS_PENDING,
            'parent_confirmed_at' => null,
            'parent_confirmed_by' => null,
            'parent_confirmation_channel' => null,
            'parent_note' => null,
            'approved_class_section_id' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_note' => $adminNote ?? $confirmation->admin_note,
        ]);

        return $confirmation->fresh();
    }

    public function recordParentConfirmation(
        ReenrollmentConfirmation $confirmation,
        string $channel,
        ?User $actor = null,
        ?string $note = null,
        bool $decline = false
    ): ReenrollmentConfirmation {
        if (in_array($confirmation->status, [
            ReenrollmentConfirmation::STATUS_CONFIRMED,
            ReenrollmentConfirmation::STATUS_REJECTED,
            ReenrollmentConfirmation::STATUS_EXPIRED,
        ], true)) {
            throw new \RuntimeException(__('reenrollment.errors.not_modifiable'));
        }

        if ($decline) {
            $confirmation->update([
                'status' => ReenrollmentConfirmation::STATUS_DECLINED,
                'parent_confirmation_channel' => $channel,
                'parent_confirmed_at' => now(),
                'parent_confirmed_by' => $actor?->id,
                'parent_note' => $note,
            ]);

            $this->notifyLifecycle($confirmation->fresh(['student', 'campaign']), 'reenrollment_declined');

            return $confirmation->fresh();
        }

        $this->refreshPayment($confirmation);
        $confirmation->refresh();

        $required = (float) $confirmation->amount_required;
        $paid = (float) $confirmation->amount_paid;
        $feeMet = $required <= 0 || $paid >= $required;

        $confirmation->update([
            'status' => $feeMet
                ? ReenrollmentConfirmation::STATUS_PENDING_REVIEW
                : ReenrollmentConfirmation::STATUS_PARTIAL,
            'parent_confirmation_channel' => $channel,
            'parent_confirmed_at' => now(),
            'parent_confirmed_by' => $actor?->id,
            'parent_note' => $note,
            'payment_status' => $this->paymentStatusLabel($required, $paid),
        ]);

        $event = $feeMet ? 'reenrollment_confirmation_received' : 'reenrollment_partial_confirmation';
            $this->notifyLifecycle($confirmation->fresh(['student', 'campaign.toSession']), $event);

        return $confirmation->fresh();
    }

    public function keepPending(ReenrollmentConfirmation $confirmation, ?string $adminNote = null): ReenrollmentConfirmation
    {
        if (! $confirmation->isInReviewQueue()) {
            throw new \RuntimeException(__('reenrollment.errors.not_in_queue'));
        }

        $confirmation->update([
            'admin_note' => $adminNote ?? $confirmation->admin_note,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            // status stays pending_review / partial_confirmation
        ]);

        return $confirmation->fresh();
    }

    public function reject(ReenrollmentConfirmation $confirmation, ?string $adminNote = null): ReenrollmentConfirmation
    {
        if (! in_array($confirmation->status, array_merge(
            ReenrollmentConfirmation::QUEUE_STATUSES,
            [ReenrollmentConfirmation::STATUS_PENDING]
        ), true)) {
            throw new \RuntimeException(__('reenrollment.errors.not_modifiable'));
        }

        $confirmation->update([
            'status' => ReenrollmentConfirmation::STATUS_REJECTED,
            'admin_note' => $adminNote,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->notifyLifecycle($confirmation->fresh(['student', 'campaign']), 'reenrollment_rejected');

        return $confirmation->fresh();
    }

    /**
     * Approve re-enrollment and create the next-session enrollment (promotion assignment).
     */
    public function approve(
        ReenrollmentConfirmation $confirmation,
        int $targetClassSectionId,
        ?string $adminNote = null,
        bool $requireFeeMet = true
    ): ReenrollmentConfirmation {
        if (! $confirmation->isInReviewQueue()) {
            throw new \RuntimeException(__('reenrollment.errors.not_in_queue'));
        }

        $this->refreshPayment($confirmation);
        $confirmation->refresh();

        $required = (float) $confirmation->amount_required;
        $paid = (float) $confirmation->amount_paid;
        if ($requireFeeMet && $required > 0 && $paid < $required) {
            throw new \RuntimeException(__('reenrollment.errors.fee_not_met'));
        }

        $campaign = $confirmation->campaign;
        $targetClass = ClassSection::with('gradeLevel')->findOrFail($targetClassSectionId);

        if ((int) $targetClass->institution_id !== (int) $confirmation->institution_id) {
            abort(403);
        }

        return DB::transaction(function () use ($confirmation, $campaign, $targetClass, $adminNote) {
            // Close previous enrollment
            if ($confirmation->from_enrollment_id) {
                StudentEnrollment::where('id', $confirmation->from_enrollment_id)
                    ->update(['status' => 'promoted']);
            }

            $targetEnrollment = StudentEnrollment::firstOrCreate(
                [
                    'academic_session_id' => $campaign->to_academic_session_id,
                    'student_id' => $confirmation->student_id,
                ],
                [
                    'institution_id' => $confirmation->institution_id,
                    'grade_level_id' => $targetClass->grade_level_id,
                    'class_section_id' => $targetClass->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'roll_number' => null,
                ]
            );

            if ($targetEnrollment->wasRecentlyCreated === false) {
                $targetEnrollment->update([
                    'grade_level_id' => $targetClass->grade_level_id,
                    'class_section_id' => $targetClass->id,
                    'status' => 'active',
                ]);
            }

            $confirmation->update([
                'status' => ReenrollmentConfirmation::STATUS_CONFIRMED,
                'approved_class_section_id' => $targetClass->id,
                'proposed_class_section_id' => $confirmation->proposed_class_section_id ?: $targetClass->id,
                'target_enrollment_id' => $targetEnrollment->id,
                'admin_note' => $adminNote,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'payment_status' => $this->paymentStatusLabel(
                    (float) $confirmation->amount_required,
                    (float) $confirmation->amount_paid
                ),
            ]);

            $fresh = $confirmation->fresh(['student', 'campaign', 'approvedClassSection']);
            $this->notifyLifecycle($fresh, 'reenrollment_confirmed');

            return $fresh;
        });
    }

    public function refreshPayment(ReenrollmentConfirmation $confirmation): void
    {
        $campaign = $confirmation->campaign;
        $totalPaid = $this->computePaidAmount($campaign, (int) $confirmation->student_id);
        $required = (float) ($confirmation->amount_required ?: $campaign->min_fee_amount);

        $confirmation->update([
            'amount_required' => $required,
            'amount_paid' => $totalPaid,
            'payment_status' => $this->paymentStatusLabel($required, $totalPaid),
        ]);

        // Escalate partial → pending_review when fee becomes met after parent already confirmed
        if (
            $confirmation->status === ReenrollmentConfirmation::STATUS_PARTIAL
            && ($required <= 0 || $totalPaid >= $required)
            && $confirmation->parent_confirmed_at
        ) {
            $confirmation->update(['status' => ReenrollmentConfirmation::STATUS_PENDING_REVIEW]);
        }
    }

    public function buildReviewSummary(ReenrollmentConfirmation $confirmation): array
    {
        $confirmation->loadMissing([
            'student.parent',
            'campaign.fromSession',
            'campaign.toSession',
            'fromClassSection.gradeLevel',
            'proposedClassSection.gradeLevel',
            'fromEnrollment',
        ]);

        $student = $confirmation->student;
        $campaign = $confirmation->campaign;
        $this->refreshPayment($confirmation);
        $confirmation->refresh();

        $firstEnrollment = StudentEnrollment::where('student_id', $student->id)
            ->orderBy('enrolled_at')
            ->orderBy('id')
            ->first();
        $admissionDate = $student->admission_date
            ?? $firstEnrollment?->enrolled_at
            ?? $student->created_at;
        $yearsInSchool = $admissionDate
            ? max(0, (int) $admissionDate->diffInYears(now()))
            : 0;

        $sessionId = $campaign->from_academic_session_id;

        $invoices = Invoice::where('student_id', $student->id)->get();
        $sessionInvoices = $invoices->where('academic_session_id', $sessionId);
        $priorInvoices = $invoices->where('academic_session_id', '!=', $sessionId);

        $sessionPaid = (float) $sessionInvoices->sum('paid_amount');
        $sessionOutstanding = (float) $sessionInvoices
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->sum(fn ($i) => max(0, (float) $i->total_amount - (float) $i->paid_amount));
        $priorOutstanding = (float) $priorInvoices
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->sum(fn ($i) => max(0, (float) $i->total_amount - (float) $i->paid_amount));

        $attendance = StudentAttendance::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $examAvg = ExamRecord::where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('academic_session_id', $sessionId))
            ->avg('marks_obtained');

        $examCount = ExamRecord::where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('academic_session_id', $sessionId))
            ->count();

        $disciplineCount = DisciplinaryRecord::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('status', '!=', 'cancelled')
            ->count();

        $conductRecords = StudentConductRecord::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->latest()
            ->limit(5)
            ->get();

        $currentClass = class_section_label($confirmation->fromClassSection);
        $proposedClass = class_section_label($confirmation->proposedClassSection);

        return [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'student_name' => $student->full_name,
            'admission_date' => $admissionDate,
            'years_in_school' => $yearsInSchool,
            'current_class' => $currentClass,
            'proposed_class' => $proposedClass,
            'from_session' => $campaign->fromSession?->name,
            'to_session' => $campaign->toSession?->name,
            'exam_records_count' => $examCount,
            'exam_average' => $examAvg !== null ? round((float) $examAvg, 2) : null,
            'annual_status' => $this->guessAnnualStatus($examAvg, $examCount),
            'fees_paid_session' => $sessionPaid,
            'fees_outstanding_session' => $sessionOutstanding,
            'fees_outstanding_prior' => $priorOutstanding,
            'attendance' => [
                'present' => (int) ($attendance['present'] ?? $attendance['Present'] ?? 0),
                'absent' => (int) ($attendance['absent'] ?? $attendance['Absent'] ?? 0),
                'late' => (int) ($attendance['late'] ?? $attendance['Late'] ?? 0),
                'excused' => (int) ($attendance['excused'] ?? $attendance['Excused'] ?? 0),
            ],
            'discipline_count' => $disciplineCount,
            'conduct_records' => $conductRecords,
            'parent_confirmation_channel' => $confirmation->parent_confirmation_channel,
            'parent_confirmed_at' => $confirmation->parent_confirmed_at,
            'parent_note' => $confirmation->parent_note,
            'amount_required' => (float) $confirmation->amount_required,
            'amount_paid' => (float) $confirmation->amount_paid,
            'payment_status' => $confirmation->payment_status,
            'remaining_for_reenroll' => max(0, (float) $confirmation->amount_required - (float) $confirmation->amount_paid),
            'status' => $confirmation->status,
        ];
    }

    public function openCampaignForInstitution(int $institutionId): ?ReenrollmentCampaign
    {
        return ReenrollmentCampaign::where('institution_id', $institutionId)
            ->where('status', ReenrollmentCampaign::STATUS_OPEN)
            ->latest('id')
            ->get()
            ->first(fn (ReenrollmentCampaign $c) => $c->isOpen());
    }

    public function confirmationForStudent(int $institutionId, int $studentId): ?ReenrollmentConfirmation
    {
        $campaign = $this->openCampaignForInstitution($institutionId);
        if (! $campaign) {
            return null;
        }

        return ReenrollmentConfirmation::where('campaign_id', $campaign->id)
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * Students already confirmed for a target session (eligible for bulk promotion fallback).
     *
     * @return Collection<int, int> student IDs
     */
    public function confirmedStudentIdsForTargetSession(int $institutionId, int $toSessionId): Collection
    {
        return ReenrollmentConfirmation::query()
            ->where('institution_id', $institutionId)
            ->where('status', ReenrollmentConfirmation::STATUS_CONFIRMED)
            ->whereHas('campaign', fn ($q) => $q->where('to_academic_session_id', $toSessionId))
            ->pluck('student_id');
    }

    /**
     * Whether re-enrollment gates bulk promotion into a session. A closed campaign
     * still gates it: the window ended, so unconfirmed students stay out.
     */
    public function hasOpenCampaignForTarget(int $institutionId, int $toSessionId): bool
    {
        return ReenrollmentCampaign::where('institution_id', $institutionId)
            ->where('to_academic_session_id', $toSessionId)
            ->whereIn('status', [ReenrollmentCampaign::STATUS_OPEN, ReenrollmentCampaign::STATUS_CLOSED])
            ->exists();
    }

    protected function sessionPaidAmount(int $studentId, int $sessionId): float
    {
        return (float) Invoice::where('student_id', $studentId)
            ->where('academic_session_id', $sessionId)
            ->sum('paid_amount');
    }

    /**
     * Money that counts towards the re-enrollment deposit:
     * everything paid against the next session, plus deposits taken on the
     * current session on/after the day the campaign opened. Fees paid earlier
     * in the year belong to the outgoing session and must not count here.
     */
    protected function computePaidAmount(ReenrollmentCampaign $campaign, int $studentId): float
    {
        $paid = (float) Payment::whereHas(
            'invoice',
            fn ($q) => $q->where('student_id', $studentId)
                ->where('academic_session_id', $campaign->to_academic_session_id)
        )->sum('amount');

        if ($campaign->opens_at) {
            $paid += (float) Payment::whereHas(
                'invoice',
                fn ($q) => $q->where('student_id', $studentId)
                    ->where('academic_session_id', $campaign->from_academic_session_id)
            )
                ->whereDate('payment_date', '>=', $campaign->opens_at->toDateString())
                ->sum('amount');
        }

        return round($paid, 2);
    }

    protected function paymentStatusLabel(float $required, float $paid): string
    {
        if ($required <= 0) {
            return 'paid';
        }
        if ($paid <= 0) {
            return 'pending';
        }
        if ($paid < $required) {
            return 'partial';
        }

        return 'paid';
    }

    protected function guessAnnualStatus(?float $examAvg, int $examCount): string
    {
        if ($examCount === 0 || $examAvg === null) {
            return __('reenrollment.annual_status_pending');
        }
        // Heuristic: many schools use /20 or /100 — treat >= 50% of likely max poorly; show average only label
        return __('reenrollment.annual_status_with_avg', ['avg' => number_format($examAvg, 2)]);
    }

    protected function notifyLifecycle(ReenrollmentConfirmation $confirmation, string $eventKey): void
    {
        try {
            $student = $confirmation->student;
            if (! $student) {
                return;
            }

            $ctx = student_notification_context($student);
            $currentClass = class_section_label($confirmation->fromClassSection);
            $data = array_merge($ctx, [
                'Class' => $currentClass ?: ($ctx['Class'] ?? ''),
                'Status' => $confirmation->statusLabel(),
                'Campaign' => $confirmation->campaign?->name ?? '',
                'Session' => $confirmation->campaign?->toSession?->name ?? ($ctx['Session'] ?? ''),
                'Deadline' => optional($confirmation->campaign?->closes_at)->format('d/m/Y')
                    ?: __('reenrollment.no_deadline'),
                'AmountRequired' => number_format((float) $confirmation->amount_required, 2),
                'AmountPaid' => number_format((float) $confirmation->amount_paid, 2),
                'Remaining' => number_format(max(0, (float) $confirmation->amount_required - (float) $confirmation->amount_paid), 2),
                'ConfirmationDate' => optional($confirmation->parent_confirmed_at)->format('d/m/Y H:i') ?? '',
            ]);

            $this->notifications->sendEventToStudentContact($eventKey, $student, $data);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
