<?php

namespace App\Jobs;

use App\Models\SecondaryDeliberation;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyResitParentsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public int $institutionId,
        public int $sessionId
    ) {
    }

    public function handle(NotificationService $notifications): void
    {
        $rows = SecondaryDeliberation::with([
            'student.parent',
            'student.institution',
            'classSection',
            'academicSession',
        ])
            ->where('institution_id', $this->institutionId)
            ->where('academic_session_id', $this->sessionId)
            ->where('decision', '!=', SecondaryDeliberation::DECISION_PENDING)
            ->whereNull('notified_at')
            ->get();

        $eventKey = 'resit_notification';

        foreach ($rows as $row) {
            try {
                $student = $row->student;
                if (! $student) {
                    continue;
                }

                $parent = $student->parent;
                $phone = $parent?->father_phone
                    ?? $parent?->mother_phone
                    ?? $parent?->guardian_phone
                    ?? $student->mobile_number;

                if (! $phone) {
                    continue;
                }

                $decisionLabel = __('secondary_deliberation.decision_' . $row->decision);
                $payload = [
                    'ParentName' => $parent?->full_name ?? $parent?->father_name ?? '',
                    'StudentName' => $student->full_name ?? trim($student->first_name . ' ' . $student->last_name),
                    'ClassName' => class_section_label($row->classSection),
                    'ResitSubjects' => implode(', ', $row->failed_subjects ?? []),
                    'Decision' => $decisionLabel,
                    'Status' => $decisionLabel,
                    'SchoolName' => $student->institution?->name ?? config('app.name'),
                    'AcademicYear' => $row->academicSession?->name ?? '',
                ];

                $related = [
                    'related_type' => SecondaryDeliberation::class,
                    'related_id' => $row->id,
                    'event_key' => $eventKey,
                ];

                $sent = false;
                if ($notifications->channelEnabled($this->institutionId, $eventKey, 'whatsapp')) {
                    $wa = $notifications->sendNotificationEvent(
                        $eventKey,
                        $phone,
                        $payload,
                        $this->institutionId,
                        'whatsapp',
                        $related
                    );
                    $sent = ! empty($wa['success']);
                }

                if (! $sent && $notifications->channelEnabled($this->institutionId, $eventKey, 'sms')) {
                    $sms = $notifications->sendNotificationEvent(
                        $eventKey,
                        $phone,
                        $payload,
                        $this->institutionId,
                        'sms',
                        $related
                    );
                    $sent = ! empty($sms['success']);
                }

                if ($sent) {
                    $row->update(['notified_at' => now()]);
                }
            } catch (Throwable $e) {
                Log::warning('NotifyResitParentsJob student failed', [
                    'deliberation_id' => $row->id,
                    'student_id' => $row->student_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
