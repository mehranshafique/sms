<?php

namespace App\Jobs;

use App\Models\ChatSession;
use App\Models\SchoolEvent;
use App\Models\SchoolEventInvitation;
use App\Models\User;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendSchoolEventInvitationsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(
        public int $schoolEventId,
        public int $requestedByUserId
    ) {}

    public function handle(NotificationService $notifications, InAppNotificationService $inApp): void
    {
        try {
            $this->processInvitations($notifications, $inApp);
        } catch (Throwable $e) {
            $this->failed($e);
        }
    }

    private function processInvitations(NotificationService $notifications, InAppNotificationService $inApp): void
    {
        $schoolEvent = SchoolEvent::with([
            'invitations.student.enrollments.classSection.gradeLevel',
            'institution',
        ])->find($this->schoolEventId);

        if (!$schoolEvent) {
            return;
        }

        $stats = ['sent' => 0, 'partial' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($schoolEvent->invitations as $invitation) {
            $payload = $this->invitationPayload($schoolEvent, $invitation);
            $meta = [];
            $attempted = 0;
            $succeeded = 0;
            $related = [
                'related_type' => SchoolEventInvitation::class,
                'related_id' => $invitation->id,
            ];

            if ($invitation->recipient_phone) {
                if ($notifications->channelEnabled($schoolEvent->institution_id, 'event_invitation', 'sms')) {
                    $attempted++;
                    $sms = $notifications->sendNotificationEvent(
                        'event_invitation',
                        $invitation->recipient_phone,
                        $payload,
                        $schoolEvent->institution_id,
                        'sms',
                        $related
                    );
                    $meta['sms'] = !empty($sms['success']) ? 'sent' : 'failed';
                    if (!empty($sms['success'])) {
                        $succeeded++;
                    } else {
                        $meta['sms_error'] = mb_substr((string) ($sms['message'] ?? 'failed'), 0, 120);
                    }
                }

                if ($notifications->channelEnabled($schoolEvent->institution_id, 'event_invitation', 'whatsapp')) {
                    $attempted++;
                    $wa = $notifications->sendNotificationEvent(
                        'event_invitation',
                        $invitation->recipient_phone,
                        $payload,
                        $schoolEvent->institution_id,
                        'whatsapp',
                        $related
                    );
                    $meta['whatsapp'] = !empty($wa['success']) ? 'sent' : 'failed';
                    if (!empty($wa['success'])) {
                        $succeeded++;
                    } else {
                        $meta['whatsapp_error'] = mb_substr((string) ($wa['message'] ?? 'failed'), 0, 120);
                    }
                }
            }

            if ($invitation->recipient_email && $notifications->channelEnabled($schoolEvent->institution_id, 'event_invitation', 'email')) {
                $attempted++;
                $email = $notifications->sendEmailTemplate(
                    'event_invitation',
                    $invitation->recipient_email,
                    $payload,
                    $schoolEvent->institution_id
                );
                $meta['email'] = !empty($email['success']) ? 'sent' : 'failed';
                if (!empty($email['success'])) {
                    $succeeded++;
                } else {
                    $meta['email_error'] = mb_substr((string) ($email['message'] ?? 'failed'), 0, 120);
                }
            }

            if ($invitation->recipient_telegram_chat_id) {
                $attempted++;
                $template = \App\Models\SmsTemplate::forEvent('event_invitation', $schoolEvent->institution_id)->first();
                $text = $template
                    ? apply_sms_template_tags($template->body, $payload)
                    : ($payload['EventName'] ?? $schoolEvent->name);
                $tg = $notifications->sendTelegramMessage($invitation->recipient_telegram_chat_id, $text);
                $meta['telegram'] = !empty($tg['success']) ? 'sent' : 'failed';
                if (!empty($tg['success'])) {
                    $succeeded++;
                } else {
                    $meta['telegram_error'] = mb_substr((string) ($tg['message'] ?? 'failed'), 0, 120);
                }
            }

            if ($attempted === 0) {
                $status = 'failed';
                $meta['note'] = 'no_channel';
                $stats['skipped']++;
            } elseif ($succeeded === $attempted) {
                $status = 'sent';
                $stats['sent']++;
            } elseif ($succeeded > 0) {
                $status = 'partial';
                $stats['partial']++;
            } else {
                $status = 'failed';
                $stats['failed']++;
            }

            $invitation->update([
                'delivery_status' => $status,
                'delivery_meta' => $meta,
                'sent_at' => $succeeded > 0 ? now() : $invitation->sent_at,
            ]);
        }

        $schoolEvent->update(['status' => 'sent']);

        $summary = __('school_event.sent_summary', [
            'sent' => $stats['sent'],
            'partial' => $stats['partial'],
            'failed' => $stats['failed'],
        ]);

        $this->notifyRequester(
            $inApp,
            $schoolEvent,
            __('school_event.job_done_title'),
            $summary,
            ($stats['failed'] > 0 || $stats['partial'] > 0) ? 'fa-exclamation-triangle' : 'fa-check-circle'
        );
    }

    public function failed(?Throwable $e): void
    {
        Log::error('SendSchoolEventInvitationsJob failed', [
            'school_event_id' => $this->schoolEventId,
            'error' => $e?->getMessage(),
        ]);

        $schoolEvent = SchoolEvent::find($this->schoolEventId);
        if ($schoolEvent && $schoolEvent->status === 'sending') {
            $schoolEvent->update(['status' => 'draft']);
        }

        try {
            $inApp = app(InAppNotificationService::class);
            if ($schoolEvent) {
                $this->notifyRequester(
                    $inApp,
                    $schoolEvent,
                    __('school_event.job_failed_title'),
                    __('school_event.job_failed_body', ['name' => $schoolEvent->name]),
                    'fa-times-circle'
                );
            }
        } catch (Throwable $notifyError) {
            Log::warning('Failed to notify user after invitation job failure: ' . $notifyError->getMessage());
        }
    }

    private function notifyRequester(
        InAppNotificationService $inApp,
        SchoolEvent $schoolEvent,
        string $title,
        string $message,
        string $icon
    ): void {
        $user = User::find($this->requestedByUserId);
        if (!$user) {
            return;
        }

        $inApp->notifyUser(
            $user,
            'system_alert',
            'school_event_invitations',
            $title,
            $message,
            route('school-events.show', $schoolEvent),
            $schoolEvent->institution_id,
            $icon,
            [
                'school_event_id' => $schoolEvent->id,
                'toast' => true,
            ]
        );
    }

    private function invitationPayload(SchoolEvent $schoolEvent, SchoolEventInvitation $invitation): array
    {
        $student = $invitation->student;
        $enrollment = $student?->enrollments?->where('status', 'active')->first();
        $ticket = 'EVT-' . strtoupper(Str::random(6));

        return [
            'ParentName' => $invitation->recipient_name,
            'StudentName' => $student?->full_name ?? '',
            'ClassName' => class_section_label($enrollment?->classSection),
            'EventName' => $schoolEvent->name,
            'EventDate' => localized_date($schoolEvent->event_date, 'd M Y'),
            'EventTime' => $schoolEvent->event_time ? substr((string) $schoolEvent->event_time, 0, 5) : '',
            'Venue' => $schoolEvent->venue ?? '',
            'TicketNumber' => $ticket,
            'SchoolName' => $schoolEvent->institution?->name ?? config('app.name'),
        ];
    }
}
