<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\FundRequest;
use App\Models\InAppNotification;
use App\Models\Invoice;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\StaffLeave;
use App\Models\StudentPickup;
use App\Models\StudentRequest;
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

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = InAppNotification::where('user_id', $userId)->find($notificationId);
        if (!$notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
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
            'type' => __('requests.type_' . $req->type),
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
            'staff' => [RoleEnum::TEACHER->value, RoleEnum::STAFF->value, ...self::ADMIN_ROLES],
            'student' => [RoleEnum::STUDENT->value],
            'parent' => [RoleEnum::GUARDIAN->value],
            default => [
                RoleEnum::STUDENT->value,
                RoleEnum::TEACHER->value,
                RoleEnum::STAFF->value,
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

    private function usersForInstitutionRoles(int $institutionId, array $roles): Collection
    {
        return User::role($roles)
            ->where(function ($query) use ($institutionId) {
                $query->where('institute_id', $institutionId)
                    ->orWhereHas('institutes', fn ($q) => $q->where('institutions.id', $institutionId));
            })
            ->get();
    }
}
