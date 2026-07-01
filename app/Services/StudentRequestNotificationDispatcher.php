<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\InstitutionSetting;
use App\Models\StudentRequest;
use App\Models\User;

class StudentRequestNotificationDispatcher
{
    public function __construct(
        protected NotificationService $notifications,
        protected InAppNotificationService $inApp,
        protected NotificationPreferenceService $preferences,
    ) {}

    public function onSubmitted(StudentRequest $req): void
    {
        $req->loadMissing(['student.parent', 'student.institution']);

        if ($req->status === 'pending' || $req->status === 'submitted') {
            $this->inApp->notifyStudentRequestSubmitted($req);
            $this->notifyStaffExternal($req);
            $this->notifyParentSubmitted($req);
        }
    }

    public function onUpdated(StudentRequest $req): void
    {
        $req->loadMissing(['student.parent', 'student.institution']);
        $this->notifications->sendRequestUpdatedNotification($req);
        $this->inApp->notifyStudentRequestUpdated($req);
    }

    private function notifyStaffExternal(StudentRequest $req): void
    {
        $eventKey = 'request_submitted';
        if (!$this->shouldNotifyStaff($req->institution_id, $eventKey)) {
            return;
        }

        $student = $req->student;
        if (!$student) {
            return;
        }

        $data = [
            'StudentName' => $student->full_name,
            'RequestType' => $req->typeLabel(),
            'TicketNumber' => $req->ticket_number,
            'SchoolName' => $student->institution?->name ?? config('app.name'),
        ];

        foreach ($this->staffPhones($req->institution_id) as $phone) {
            if ($this->preferences->isChannelEnabled($req->institution_id, $eventKey, 'sms')) {
                $this->notifications->sendNotificationEvent($eventKey, $phone, $data, $req->institution_id, 'sms');
            }
            if ($this->preferences->isChannelEnabled($req->institution_id, $eventKey, 'whatsapp')) {
                $this->notifications->sendNotificationEvent($eventKey, $phone, $data, $req->institution_id, 'whatsapp');
            }
        }
    }

    private function notifyParentSubmitted(StudentRequest $req): void
    {
        if (!InstitutionSetting::get($req->institution_id, 'request_notify_parent_on_submit', true)) {
            return;
        }

        $eventKey = 'request_submitted_parent';
        $student = $req->student;
        if (!$student) {
            return;
        }

        $phone = $this->parentPhone($student);
        if (!$phone) {
            return;
        }

        $responseHours = (int) InstitutionSetting::get($req->institution_id, 'request_response_hours', 24);

        $data = [
            'StudentName' => $student->full_name,
            'TicketNumber' => $req->ticket_number,
            'RequestType' => $req->typeLabel(),
            'ResponseTime' => "{$responseHours} " . __('requests.hours'),
            'SchoolName' => $student->institution?->name ?? config('app.name'),
        ];

        $whatsappOnly = InstitutionSetting::get($req->institution_id, 'request_submit_whatsapp_only', false);

        if (!$whatsappOnly && $this->preferences->isChannelEnabled($req->institution_id, $eventKey, 'sms')) {
            $this->notifications->sendNotificationEvent($eventKey, $phone, $data, $req->institution_id, 'sms');
        }
        if ($this->preferences->isChannelEnabled($req->institution_id, $eventKey, 'whatsapp')) {
            $this->notifications->sendNotificationEvent($eventKey, $phone, $data, $req->institution_id, 'whatsapp');
        }
    }

    private function shouldNotifyStaff(?int $institutionId, string $eventKey): bool
    {
        return $this->preferences->isChannelEnabled($institutionId, $eventKey, 'sms')
            || $this->preferences->isChannelEnabled($institutionId, $eventKey, 'whatsapp');
    }

    /** @return list<string> */
    private function staffPhones(int $institutionId): array
    {
        $roles = [
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
            'Accountant',
            'accountant',
        ];

        return User::role($roles)
            ->where(function ($q) use ($institutionId) {
                $q->where('institute_id', $institutionId)
                    ->orWhereHas('institutes', fn ($iq) => $iq->where('institutions.id', $institutionId));
            })
            ->whereNotNull('phone')
            ->pluck('phone')
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    private function parentPhone($student): ?string
    {
        $parent = $student->parent;
        if ($parent) {
            $field = ($parent->primary_guardian ?? 'father') . '_phone';
            return $parent->$field ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone;
        }

        return $student->mobile_number;
    }
}
