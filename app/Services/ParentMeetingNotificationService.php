<?php

namespace App\Services;

use App\Models\ParentMeeting;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ParentMeetingNotificationService
{
    public const EVENT_KEY = 'ptm_created';

    public function __construct(
        protected NotificationService $notifications,
        protected InAppNotificationService $inApp,
    ) {}

    /**
     * Notify parents/students for one or more newly created PTM rows.
     * Dedupes by parent phone / parent user so class fan-out does not spam.
     *
     * @param  ParentMeeting|Collection<int, ParentMeeting>|iterable<ParentMeeting>  $meetings
     */
    public function notifyCreated($meetings): void
    {
        $items = EloquentCollection::make(
            Collection::wrap($meetings)->filter()->values()->all()
        );
        if ($items->isEmpty()) {
            return;
        }

        $items->loadMissing([
            'student.parent.user',
            'student.user',
            'student.institution',
            'classSection.gradeLevel',
            'institution',
        ]);

        $seenPhones = [];
        $seenUserIds = [];

        foreach ($items as $meeting) {
            try {
                $this->notifyOne($meeting, $seenPhones, $seenUserIds);
            } catch (\Throwable $e) {
                Log::warning('PTM notification failed', [
                    'meeting_id' => $meeting->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, true>  $seenPhones
     * @param  array<int, true>  $seenUserIds
     */
    private function notifyOne(ParentMeeting $meeting, array &$seenPhones, array &$seenUserIds): void
    {
        $student = $meeting->student;
        if (!$student) {
            return;
        }

        $institutionId = $meeting->institution_id;
        $classLabel = $meeting->classSection && function_exists('class_section_label')
            ? class_section_label($meeting->classSection)
            : ($meeting->classSection->name ?? '—');

        $dateLabel = $meeting->preferred_date
            ? $meeting->preferred_date->format('d M Y')
            : '—';

        $scopeLabel = $meeting->isClassScope()
            ? __('ptm.scope_class')
            : __('ptm.scope_individual');

        $data = [
            'StudentName' => $student->full_name,
            'ParentName' => $student->parent?->father_name
                ?? $student->parent?->mother_name
                ?? 'Parent',
            'Topic' => $meeting->topic,
            'Date' => $dateLabel,
            'ClassName' => $classLabel,
            'Scope' => $scopeLabel,
            'Status' => __('ptm.status_' . $meeting->status),
            'SchoolName' => $meeting->institution?->name
                ?? $student->institution?->name
                ?? config('app.name'),
        ];

        $title = __('header.notif_ptm_title');
        $message = __('header.notif_ptm_message', [
            'student' => $student->full_name,
            'topic' => $meeting->topic,
            'date' => $dateLabel,
            'scope' => $scopeLabel,
        ]);

        $link = route('ptm.show', $meeting);

        // In-app for linked guardian / student accounts (dedupe users).
        foreach ([$student->parent?->user, $student->user] as $user) {
            if (!$user || isset($seenUserIds[$user->id])) {
                continue;
            }
            $seenUserIds[$user->id] = true;
            $this->inApp->notifyUser(
                $user,
                self::EVENT_KEY,
                'ptm',
                $title,
                $message,
                $link,
                $institutionId,
                'fa-users',
                [
                    'parent_meeting_id' => $meeting->id,
                    'batch_id' => $meeting->batch_id,
                    'scope' => $meeting->scope,
                ]
            );
        }

        // External SMS / WhatsApp to parent contact (dedupe phones).
        $parent = $student->parent;
        $phone = null;
        if ($parent) {
            $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
            $phone = $parent->$phoneField
                ?? $parent->father_phone
                ?? $parent->mother_phone
                ?? $parent->guardian_phone;
        }
        $phone = $phone ?: $student->mobile_number;

        if (!$phone) {
            return;
        }

        $phoneKey = preg_replace('/\D+/', '', (string) $phone) ?: (string) $phone;
        if (isset($seenPhones[$phoneKey])) {
            return;
        }
        $seenPhones[$phoneKey] = true;

        $this->notifications->sendEventToPhone(
            self::EVENT_KEY,
            $phone,
            $data,
            $institutionId
        );
    }
}
