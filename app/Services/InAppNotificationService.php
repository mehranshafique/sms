<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\FundRequest;
use App\Models\Budget;
use App\Models\DisciplinaryRecord;
use App\Models\InAppNotification;
use App\Models\Invoice;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\PaymentProofSubmission;
use App\Models\StaffLeave;
use App\Models\StudentPickup;
use App\Models\StudentRequest;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class InAppNotificationService
{
    /** Maps internal notification types to configuration event keys (notify_{key}). */
    public const EVENT_KEYS = [
        'student_request_new' => 'request_submitted',
        'student_request' => 'request_updated',
        'payment' => 'payment_received',
        'invoice' => 'invoice_created',
        'notice' => 'notice_published',
        'exam' => 'exam_published',
        'pickup_scan' => 'pickup_scan',
        'pickup' => 'pickup_status_updated',
        'staff_leave_new' => 'staff_leave_submitted',
        'staff_leave' => 'staff_leave_updated',
        'fund_request_new' => 'fund_request_submitted',
        'fund_request' => 'fund_request_processed',
        'budget_consumed' => 'budget_consumed',
        'disciplinary' => 'disciplinary_incident',
        'payment_proof_submitted' => 'payment_proof_submitted',
        'payment_proof_rejected' => 'payment_proof_rejected',
    ];

    private const ADMIN_ROLES = [
        RoleEnum::SUPER_ADMIN->value,
        RoleEnum::SCHOOL_ADMIN->value,
        RoleEnum::HEAD_OFFICER->value,
    ];

    private const FINANCE_ROLES = [
        RoleEnum::SUPER_ADMIN->value,
        RoleEnum::SCHOOL_ADMIN->value,
        RoleEnum::HEAD_OFFICER->value,
        'Accountant',
        'accountant',
    ];

    public function __construct(
        protected NotificationPreferenceService $preferences
    ) {}

    public function notifyUser(
        User|int $user,
        string $eventKey,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $institutionId = null,
        string $icon = 'fa-bell',
        ?array $meta = null
    ): ?InAppNotification {
        if (!$this->preferences->isSystemEnabled($institutionId, $eventKey)) {
            return null;
        }

        $userId = $user instanceof User ? $user->id : $user;

        return InAppNotification::create([
            'user_id' => $userId,
            'institution_id' => $institutionId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'icon' => $icon,
            'meta' => array_merge($meta ?? [], ['event_key' => $eventKey]),
        ]);
    }

    public function notifyUsers(
        Collection|array $users,
        string $eventKey,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $institutionId = null,
        string $icon = 'fa-bell',
        ?array $meta = null,
        ?int $exceptUserId = null
    ): void {
        if (!$this->preferences->isSystemEnabled($institutionId, $eventKey)) {
            return;
        }

        $collection = $users instanceof Collection ? $users : collect($users);

        $collection
            ->filter(fn ($user) => $user instanceof User && $user->id !== $exceptUserId)
            ->unique('id')
            ->each(fn (User $user) => $this->notifyUser(
                $user, $eventKey, $type, $title, $message, $link, $institutionId, $icon, $meta
            ));
    }

    public function notifyRoles(
        int $institutionId,
        array $roles,
        string $eventKey,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $icon = 'fa-bell',
        ?array $meta = null,
        ?int $exceptUserId = null
    ): void {
        $this->notifyUsers(
            $this->usersForInstitutionRoles($institutionId, $roles),
            $eventKey,
            $type,
            $title,
            $message,
            $link,
            $institutionId,
            $icon,
            $meta,
            $exceptUserId
        );
    }

    public function notifyAdmins(
        int $institutionId,
        string $eventKey,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $icon = 'fa-bell',
        ?array $meta = null,
        ?int $exceptUserId = null
    ): void {
        $this->notifyRoles($institutionId, self::ADMIN_ROLES, $eventKey, $type, $title, $message, $link, $icon, $meta, $exceptUserId);
    }

    public function notifyFinanceTeam(
        int $institutionId,
        string $eventKey,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $icon = 'fa-bell',
        ?array $meta = null,
        ?int $exceptUserId = null
    ): void {
        $this->notifyRoles($institutionId, self::FINANCE_ROLES, $eventKey, $type, $title, $message, $link, $icon, $meta, $exceptUserId);
    }

    public function getRecent(int $userId, int $limit = 15): Collection
    {
        return InAppNotification::where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return InAppNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /** @return array{notifications: array<int, array<string, mixed>>, unread_count: int} */
    public function feedForUser(int $userId, int $limit = 15): array
    {
        $notifications = InAppNotification::where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();

        $unreadCount = InAppNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return [
            'notifications' => $notifications->map(fn (InAppNotification $n) => [
                'id'        => $n->id,
                'title'     => $n->title,
                'message'   => $n->message,
                'link'      => $n->link,
                'icon'      => $n->icon ?: 'fa-bell',
                'is_unread' => $n->isUnread(),
                'time_ago'  => $n->created_at?->diffForHumans() ?? '',
                'type'      => $n->type,
            ])->values()->all(),
            'unread_count'  => $unreadCount,
        ];
    }

    public function markAsRead(int $notificationId, int $userId): array
    {
        $notification = InAppNotification::where('user_id', $userId)->find($notificationId);
        if (!$notification) {
            return [
                'success'      => false,
                'was_unread'   => false,
                'unread_count' => $this->getUnreadCount($userId),
            ];
        }

        $wasUnread = $notification->isUnread();

        if ($wasUnread) {
            InAppNotification::where('user_id', $userId)
                ->where('id', $notificationId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return [
            'success'      => true,
            'was_unread'   => $wasUnread,
            'unread_count' => $this->getUnreadCount($userId),
        ];
    }

    public function markAllAsRead(int $userId): int
    {
        return InAppNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    // --- Domain events ---

    public function notifyStudentRequestSubmitted(StudentRequest $req): void
    {
        if ($req->status !== 'pending') {
            return;
        }

        $eventKey = self::EVENT_KEYS['student_request_new'];
        $student = $req->student;
        $studentName = $student?->full_name ?? __('header.notif_unknown_student');
        $message = __('header.notif_request_new_message', [
            'student' => $studentName,
            'ticket' => $req->ticket_number,
            'type' => $req->typeLabel(),
        ]);

        $this->notifyAdmins(
            $req->institution_id,
            $eventKey,
            'student_request',
            __('header.notif_request_new_title'),
            $message,
            route('requests.index'),
            'fa-envelope',
            ['request_id' => $req->id],
            $req->created_by
        );

        $this->notifyRoles(
            $req->institution_id,
            [RoleEnum::TEACHER->value, RoleEnum::STAFF->value],
            $eventKey,
            'student_request',
            __('header.notif_request_new_title'),
            $message,
            route('requests.index'),
            'fa-envelope',
            ['request_id' => $req->id],
            $req->created_by
        );
    }

    public function notifyStudentRequestUpdated(StudentRequest $req): void
    {
        $eventKey = self::EVENT_KEYS['student_request'];
        $student = $req->student;
        $statusText = __('requests.status_' . $req->status);
        $title = __('header.notif_request_updated_title');
        $message = __('header.notif_request_updated_message', [
            'ticket' => $req->ticket_number,
            'status' => $statusText,
        ]);

        if ($student?->user) {
            $this->notifyUser(
                $student->user,
                $eventKey,
                'student_request',
                $title,
                $message,
                route('requests.index'),
                $req->institution_id,
                'fa-check-circle'
            );
        }

        if ($student?->parent?->user) {
            $this->notifyUser(
                $student->parent->user,
                $eventKey,
                'student_request',
                $title,
                $message,
                route('requests.index'),
                $req->institution_id,
                'fa-check-circle'
            );
        }
    }

    public function notifyPaymentReceived(Payment $payment): void
    {
        $eventKey = self::EVENT_KEYS['payment'];
        $invoice = $payment->invoice;
        $student = $invoice?->student;
        if (!$student) {
            return;
        }

        $amount = number_format($payment->amount, 2);

        if ($student->user) {
            $this->notifyUser(
                $student->user,
                $eventKey,
                'payment',
                __('header.notif_payment_title'),
                __('header.notif_payment_student_message', [
                    'amount' => $amount,
                    'invoice' => $invoice->invoice_number,
                ]),
                route('invoices.show', $invoice->id),
                $payment->institution_id,
                'fa-money-bill-wave'
            );
        }

        if ($student->parent?->user) {
            $this->notifyUser(
                $student->parent->user,
                $eventKey,
                'payment',
                __('header.notif_payment_title'),
                __('header.notif_payment_parent_message', [
                    'student' => $student->full_name,
                    'amount' => $amount,
                ]),
                route('invoices.show', $invoice->id),
                $payment->institution_id,
                'fa-money-bill-wave'
            );
        }

        $this->notifyFinanceTeam(
            $payment->institution_id,
            $eventKey,
            'payment',
            __('header.notif_payment_title'),
            __('header.notif_payment_admin_message', [
                'student' => $student->full_name,
                'amount' => $amount,
                'invoice' => $invoice->invoice_number,
            ]),
            route('invoices.show', $invoice->id),
            'fa-money-bill-wave',
            null,
            Auth::id()
        );
    }

    public function notifyPaymentProofSubmitted(PaymentProofSubmission $proof): void
    {
        $proof->loadMissing(['invoice.student.parent.user', 'invoice.student.user']);
        $invoice = $proof->invoice;
        $student = $invoice?->student;
        if (!$student) {
            return;
        }

        $eventKey = self::EVENT_KEYS['payment_proof_submitted'];
        $amount = number_format((float) $proof->amount, 2);
        $studentName = $student->full_name;
        $invoiceNo = $invoice->invoice_number ?? ('#' . $invoice->id);

        $this->notifyFinanceTeam(
            $proof->institution_id,
            $eventKey,
            'payment_proof',
            __('header.notif_proof_submitted_title'),
            __('header.notif_proof_submitted_admin_message', [
                'student' => $studentName,
                'amount' => $amount,
                'invoice' => $invoiceNo,
            ]),
            route('payment-proofs.index'),
            'fa-file-invoice',
            ['proof_id' => $proof->id]
        );

        $this->notifyStudentAndParent(
            $student,
            $eventKey,
            'payment_proof',
            __('header.notif_proof_submitted_title'),
            __('header.notif_proof_submitted_parent_message', [
                'student' => $studentName,
                'amount' => $amount,
                'invoice' => $invoiceNo,
            ]),
            route('pay.show', $invoice->payment_token),
            $proof->institution_id,
            'fa-file-upload'
        );
    }

    public function notifyPaymentProofRejected(PaymentProofSubmission $proof): void
    {
        $proof->loadMissing(['invoice.student.parent.user', 'invoice.student.user']);
        $invoice = $proof->invoice;
        $student = $invoice?->student;
        if (!$student) {
            return;
        }

        $eventKey = self::EVENT_KEYS['payment_proof_rejected'];
        $amount = number_format((float) $proof->amount, 2);
        $invoiceNo = $invoice->invoice_number ?? ('#' . $invoice->id);
        $link = $invoice->payment_token ? route('pay.show', $invoice->payment_token) : route('pay.lookup');

        $this->notifyStudentAndParent(
            $student,
            $eventKey,
            'payment_proof',
            __('header.notif_proof_rejected_title'),
            __('header.notif_proof_rejected_message', [
                'student' => $student->full_name,
                'amount' => $amount,
                'invoice' => $invoiceNo,
                'reason' => $proof->rejection_reason ?: __('header.notif_proof_no_reason'),
            ]),
            $link,
            $proof->institution_id,
            'fa-times-circle'
        );
    }

    /**
     * Notify student and/or parent user accounts when linked (respects notify_* system toggles).
     */
    private function notifyStudentAndParent(
        $student,
        string $eventKey,
        string $type,
        string $title,
        string $message,
        ?string $link,
        ?int $institutionId,
        string $icon = 'fa-bell'
    ): void {
        $targets = collect();
        if ($student->user) {
            $targets->push($student->user);
        }
        if ($student->parent?->user) {
            $targets->push($student->parent->user);
        }

        $this->notifyUsers($targets, $eventKey, $type, $title, $message, $link, $institutionId, $icon);
    }

    public function notifyInvoiceCreated(Invoice $invoice): void
    {
        $eventKey = self::EVENT_KEYS['invoice'];
        $student = $invoice->student;
        if (!$student) {
            return;
        }

        $amount = number_format($invoice->total_amount, 2);

        if ($student->user) {
            $this->notifyUser(
                $student->user,
                $eventKey,
                'invoice',
                __('header.notif_invoice_title'),
                __('header.notif_invoice_student_message', [
                    'amount' => $amount,
                    'invoice' => $invoice->invoice_number,
                ]),
                route('invoices.show', $invoice->id),
                $invoice->institution_id,
                'fa-file-invoice'
            );
        }

        if ($student->parent?->user) {
            $this->notifyUser(
                $student->parent->user,
                $eventKey,
                'invoice',
                __('header.notif_invoice_title'),
                __('header.notif_invoice_parent_message', [
                    'student' => $student->full_name,
                    'amount' => $amount,
                ]),
                route('invoices.show', $invoice->id),
                $invoice->institution_id,
                'fa-file-invoice'
            );
        }

        $this->notifyFinanceTeam(
            $invoice->institution_id,
            $eventKey,
            'invoice',
            __('header.notif_invoice_title'),
            __('header.notif_invoice_admin_message', [
                'student' => $student->full_name,
                'invoice' => $invoice->invoice_number,
            ]),
            route('invoices.show', $invoice->id),
            'fa-file-invoice',
            null,
            Auth::id()
        );
    }

    public function notifyNoticePublished(Notice $notice): void
    {
        if (!$notice->is_published) {
            return;
        }

        $eventKey = self::EVENT_KEYS['notice'];
        $roles = match ($notice->audience) {
            'staff' => [RoleEnum::TEACHER->value, ...self::ADMIN_ROLES],
            'student' => [RoleEnum::STUDENT->value],
            'parent' => [RoleEnum::GUARDIAN->value],
            default => [
                RoleEnum::STUDENT->value,
                RoleEnum::TEACHER->value,
                RoleEnum::GUARDIAN->value,
                ...self::ADMIN_ROLES,
            ],
        };

        $link = in_array(RoleEnum::STUDENT->value, $roles) && count($roles) <= 2
            ? route('student.notices.index')
            : route('notices.index');

        $this->notifyRoles(
            $notice->institution_id,
            $roles,
            $eventKey,
            'notice',
            __('header.notif_notice_title'),
            __('header.notif_notice_message', ['title' => $notice->title]),
            $link,
            'fa-bullhorn',
            ['notice_id' => $notice->id],
            $notice->created_by
        );
    }

    public function notifyExamPublished(Exam $exam): void
    {
        $eventKey = self::EVENT_KEYS['exam'];

        $this->notifyRoles(
            $exam->institution_id,
            [RoleEnum::STUDENT->value],
            $eventKey,
            'exam',
            __('header.notif_exam_title'),
            __('header.notif_exam_student_message', ['name' => $exam->name]),
            route('marks.my_marks'),
            'fa-trophy',
            ['exam_id' => $exam->id]
        );

        $this->notifyRoles(
            $exam->institution_id,
            [RoleEnum::TEACHER->value, ...self::ADMIN_ROLES],
            $eventKey,
            'exam',
            __('header.notif_exam_title'),
            __('header.notif_exam_teacher_message', ['name' => $exam->name]),
            route('exams.show', $exam->id),
            'fa-trophy',
            ['exam_id' => $exam->id]
        );
    }

    public function notifyPickupScanned(StudentPickup $pickup): void
    {
        $eventKey = self::EVENT_KEYS['pickup_scan'];
        $studentName = $pickup->student?->full_name ?? '';
        $title = __('header.notif_pickup_scan_title');
        $message = __('header.notif_pickup_scan_message', ['student' => $studentName]);

        $this->notifyRoles(
            $pickup->institution_id,
            [RoleEnum::TEACHER->value],
            $eventKey,
            'pickup',
            $title,
            $message,
            route('pickups.teacher'),
            'fa-qrcode',
            ['pickup_id' => $pickup->id]
        );

        $this->notifyAdmins(
            $pickup->institution_id,
            $eventKey,
            'pickup',
            $title,
            $message,
            route('pickups.teacher'),
            'fa-qrcode',
            ['pickup_id' => $pickup->id]
        );
    }

    /**
     * Notify the student's class teacher (in-app + FCM push) when a parent scans at the gate.
     */
    public function notifyClassTeacherPickup(StudentPickup $pickup): void
    {
        $pickup->loadMissing('student');

        $enrollment = StudentEnrollment::with(['classSection.classTeacher.user'])
            ->where('student_id', $pickup->student_id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $teacherUser = $enrollment?->classSection?->classTeacher?->user;
        if (!$teacherUser) {
            return;
        }

        $eventKey = self::EVENT_KEYS['pickup_scan'];
        $studentName = $pickup->student?->full_name ?? '';
        $title = __('header.notif_pickup_scan_title');
        if ($title === 'header.notif_pickup_scan_title') {
            $title = 'Parent at Gate';
        }
        $message = __('header.notif_pickup_scan_message', ['student' => $studentName]);
        if ($message === 'header.notif_pickup_scan_message') {
            $message = "{$studentName}'s parent is waiting at the gate for pickup.";
        }

        $this->notifyUser(
            $teacherUser,
            $eventKey,
            'pickup',
            $title,
            $message,
            route('pickups.teacher'),
            $pickup->institution_id,
            'fa-qrcode',
            ['pickup_id' => $pickup->id, 'student_id' => $pickup->student_id]
        );

        app(NotificationService::class)->sendPushNotification(
            $teacherUser->id,
            $title,
            $message,
            [
                'pickup_id' => (string) $pickup->id,
                'type' => 'pickup_requested',
                'student_id' => (string) $pickup->student_id,
            ]
        );
    }

    public function notifyPickupStatusUpdated(StudentPickup $pickup): void
    {
        $eventKey = self::EVENT_KEYS['pickup'];
        $student = $pickup->student;
        if (!$student) {
            return;
        }

        $status = ucfirst($pickup->status);
        $targets = collect();

        if ($student->parent?->user) {
            $targets->push($student->parent->user);
        }
        if ($student->user) {
            $targets->push($student->user);
        }

        $this->notifyUsers(
            $targets,
            $eventKey,
            'pickup',
            __('header.notif_pickup_status_title'),
            __('header.notif_pickup_status_message', [
                'student' => $student->full_name,
                'status' => $status,
            ]),
            route('pickups.parent'),
            $pickup->institution_id,
            'fa-check-circle'
        );
    }

    public function notifyStaffLeaveSubmitted(StaffLeave $leave): void
    {
        if ($leave->status !== 'pending') {
            return;
        }

        $eventKey = self::EVENT_KEYS['staff_leave_new'];
        $staffName = $leave->staff?->user?->name ?? __('header.notif_unknown_staff');

        $this->notifyAdmins(
            $leave->institution_id,
            $eventKey,
            'staff_leave',
            __('header.notif_leave_new_title'),
            __('header.notif_leave_new_message', ['staff' => $staffName]),
            route('staff-leaves.index'),
            'fa-calendar-minus',
            ['leave_id' => $leave->id],
            $leave->staff?->user_id
        );
    }

    public function notifyStaffLeaveUpdated(StaffLeave $leave): void
    {
        $eventKey = self::EVENT_KEYS['staff_leave'];
        $staffUser = $leave->staff?->user;
        if (!$staffUser) {
            return;
        }

        $this->notifyUser(
            $staffUser,
            $eventKey,
            'staff_leave',
            __('header.notif_leave_updated_title'),
            __('header.notif_leave_updated_message', [
                'status' => ucfirst($leave->status),
            ]),
            route('staff-leaves.index'),
            $leave->institution_id,
            'fa-calendar-check'
        );
    }

    public function notifyFundRequestSubmitted(FundRequest $fundRequest): void
    {
        $eventKey = self::EVENT_KEYS['fund_request_new'];
        $requester = User::find($fundRequest->requested_by);

        $this->notifyFinanceTeam(
            $fundRequest->institution_id,
            $eventKey,
            'fund_request',
            __('header.notif_fund_new_title'),
            __('header.notif_fund_new_message', [
                'title' => $fundRequest->title,
                'requester' => $requester?->name ?? '',
            ]),
            route('budgets.requests'),
            'fa-hand-holding-usd',
            ['fund_request_id' => $fundRequest->id],
            $fundRequest->requested_by
        );
    }

    public function notifyFundRequestProcessed(FundRequest $fundRequest): void
    {
        $eventKey = self::EVENT_KEYS['fund_request'];
        $requester = User::find($fundRequest->requested_by);
        if (!$requester) {
            return;
        }

        $this->notifyUser(
            $requester,
            $eventKey,
            'fund_request',
            __('header.notif_fund_status_title', ['status' => ucfirst($fundRequest->status)]),
            __('header.notif_fund_status_message', [
                'title' => $fundRequest->title,
                'status' => ucfirst($fundRequest->status),
            ]),
            route('budgets.requests'),
            $fundRequest->institution_id,
            $fundRequest->status === 'approved' ? 'fa-check-circle' : 'fa-times-circle'
        );
    }

    public function notifyBudgetConsumed(FundRequest $fundRequest, string $budgetLine, string $amount, string $remaining): void
    {
        $eventKey = self::EVENT_KEYS['budget_consumed'];
        $fundRequest->loadMissing(['budget.responsibleUser']);

        $title = __('header.notif_budget_consumed_title');
        $message = __('header.notif_budget_consumed_message', [
            'line' => $budgetLine,
            'title' => $fundRequest->title,
            'amount' => $amount,
            'remaining' => $remaining,
        ]);

        $this->notifyFinanceTeam(
            $fundRequest->institution_id,
            $eventKey,
            'budget_consumed',
            $title,
            $message,
            route('budgets.index'),
            'fa-chart-pie',
            ['fund_request_id' => $fundRequest->id],
            $fundRequest->requested_by
        );

        $responsible = $fundRequest->budget?->responsibleUser;
        if ($responsible) {
            $this->notifyUser(
                $responsible,
                $eventKey,
                'budget_consumed',
                $title,
                $message,
                route('budgets.index'),
                $fundRequest->institution_id,
                'fa-chart-pie'
            );
        }
    }

    public function notifyDisciplinaryIncident(DisciplinaryRecord $record): void
    {
        $record->loadMissing(['student.parent.user', 'student.user']);
        $student = $record->student;
        if (!$student) {
            return;
        }

        $eventKey = self::EVENT_KEYS['disciplinary'];
        $title = __('header.notif_discipline_title');
        $message = __('header.notif_discipline_message', [
            'student' => $student->full_name,
            'type' => $record->typeLabel(),
            'title' => $record->title,
            'date' => $record->incident_date->format('d/m/Y'),
        ]);

        if ($record->notify_parents) {
            $this->notifyStudentAndParent(
                $student,
                $eventKey,
                'disciplinary',
                $title,
                $message,
                route('discipline.show', $record->id),
                $record->institution_id,
                'fa-gavel'
            );
        }

        $this->notifyAdmins(
            $record->institution_id,
            $eventKey,
            'disciplinary',
            $title,
            $message,
            route('discipline.show', $record->id),
            'fa-gavel',
            ['disciplinary_record_id' => $record->id],
            $record->recorded_by
        );
    }

    private function usersForInstitutionRoles(int $institutionId, array $roles): Collection
    {
        $guard = config('auth.defaults.guard', 'web');
        $existing = array_values(array_filter($roles, function ($role) use ($guard) {
            return \Spatie\Permission\Models\Role::where('name', $role)->where('guard_name', $guard)->exists();
        }));

        if ($existing === []) {
            return collect();
        }

        return User::role($existing)
            ->where(function ($query) use ($institutionId) {
                $query->where('institute_id', $institutionId)
                    ->orWhereHas('institutes', fn ($q) => $q->where('institutions.id', $institutionId));
            })
            ->get();
    }
}
