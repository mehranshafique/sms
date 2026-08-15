<?php

namespace App\Services\Academic;

use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * WhatsApp-first parent alert when homework is published.
 *
 * Parents should never have to open an app to find out about new homework: the
 * message carries the essentials (class, subject, due date) and points them at
 * the school's WhatsApp chatbot for the details.
 */
class HomeworkNotificationService
{
    public const EVENT_KEY = 'homework_published';

    public function __construct(
        protected NotificationService $notifications,
        protected InAppNotificationService $inApp,
        protected HomeworkApprovalService $approvals,
    ) {}

    /**
     * Fan out to every guardian of the homework's class section.
     *
     * @return int number of parent contacts messaged
     */
    public function notifyPublished(Assignment $assignment, bool $force = false): int
    {
        if (! $assignment->isPublished()) {
            return 0;
        }

        if ($assignment->parents_notified_at && ! $force) {
            return 0;
        }

        $assignment->loadMissing(['classSection.gradeLevel', 'subject', 'institution']);

        $students = $this->recipients($assignment);
        $data = $this->templateData($assignment);

        $seenPhones = [];
        $seenUserIds = [];
        $seenEmails = [];
        $sent = 0;

        foreach ($students as $student) {
            try {
                $sent += $this->notifyForStudent($assignment, $student, $data, $seenPhones, $seenUserIds, $seenEmails);
            } catch (Throwable $e) {
                Log::warning('Homework notification failed', [
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $assignment->forceFill(['parents_notified_at' => now()])->save();

        return $sent;
    }

    /**
     * Tell the nominated approvers that homework is waiting for review.
     */
    public function notifyAwaitingApproval(Assignment $assignment): void
    {
        $assignment->loadMissing(['classSection.gradeLevel', 'subject', 'teacher.user']);

        $teacherName = $assignment->teacher?->user?->name ?? __('assignment.teacher');
        $title = __('assignment.notif_pending_title');
        $message = __('assignment.notif_pending_message', [
            'teacher' => $teacherName,
            'title' => $assignment->title,
            'class' => $this->classLabel($assignment),
        ]);

        foreach ($this->approvals->approvers($assignment->institution_id) as $approver) {
            try {
                $this->inApp->notifyUser(
                    $approver,
                    self::EVENT_KEY,
                    'homework',
                    $title,
                    $message,
                    route('assignments.index'),
                    $assignment->institution_id,
                    'fa-book',
                    ['assignment_id' => $assignment->id, 'status' => $assignment->status]
                );
            } catch (Throwable $e) {
                Log::warning('Homework approval notice failed', [
                    'assignment_id' => $assignment->id,
                    'user_id' => $approver->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Tell the teacher what happened to their homework.
     */
    public function notifyTeacherDecision(Assignment $assignment): void
    {
        $teacherUser = $assignment->loadMissing('teacher.user')->teacher?->user;

        if (! $teacherUser) {
            return;
        }

        $approved = $assignment->isPublished();

        $this->inApp->notifyUser(
            $teacherUser,
            self::EVENT_KEY,
            'homework',
            $approved ? __('assignment.notif_approved_title') : __('assignment.notif_rejected_title'),
            $approved
                ? __('assignment.notif_approved_message', ['title' => $assignment->title])
                : __('assignment.notif_rejected_message', [
                    'title' => $assignment->title,
                    'reason' => $assignment->rejection_reason ?: __('assignment.no_reason_given'),
                ]),
            route('assignments.index'),
            $assignment->institution_id,
            $approved ? 'fa-check' : 'fa-times',
            ['assignment_id' => $assignment->id, 'status' => $assignment->status]
        );
    }

    /**
     * Active students of the homework's class section.
     *
     * @return \Illuminate\Support\Collection<int, Student>
     */
    public function recipients(Assignment $assignment)
    {
        $sessionId = $assignment->academic_session_id
            ?: AcademicSession::where('institution_id', $assignment->institution_id)
                ->where('is_current', true)
                ->value('id');

        $students = StudentEnrollment::with(['student.parent.user', 'student.user'])
            ->where('class_section_id', $assignment->class_section_id)
            ->where('status', 'active')
            ->when($sessionId, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->whereHas('student', fn ($query) => $query
                ->where('institution_id', $assignment->institution_id)
                ->where('status', 'active'))
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->values();

        if ($students->isNotEmpty()) {
            return $students;
        }

        return Student::with(['parent.user', 'user'])
            ->where('institution_id', $assignment->institution_id)
            ->where('class_section_id', $assignment->class_section_id)
            ->where('status', 'active')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    public function templateData(Assignment $assignment, ?Student $student = null): array
    {
        return [
            'StudentName' => $student?->full_name ?? '',
            'ParentName' => $student?->parent?->father_name
                ?? $student?->parent?->mother_name
                ?? $student?->parent?->guardian_name
                ?? __('assignment.parent'),
            'Title' => (string) $assignment->title,
            'Subject' => (string) ($assignment->subject->name ?? '—'),
            'ClassName' => $this->classLabel($assignment),
            'Deadline' => $assignment->deadline ? $assignment->deadline->format('D d M') : '—',
            'SchoolName' => $assignment->institution->name ?? config('app.name'),
        ];
    }

    /**
     * @param  array<string, string>  $data
     * @param  array<string, true>  $seenPhones
     * @param  array<int, true>  $seenUserIds
     * @param  array<string, true>  $seenEmails
     */
    protected function notifyForStudent(
        Assignment $assignment,
        Student $student,
        array $data,
        array &$seenPhones,
        array &$seenUserIds,
        array &$seenEmails
    ): int {
        $institutionId = $assignment->institution_id;
        $payload = array_merge($data, $this->templateData($assignment, $student));

        foreach ([$student->parent?->user, $student->user] as $user) {
            if (! $user instanceof User || isset($seenUserIds[$user->id])) {
                continue;
            }

            $seenUserIds[$user->id] = true;

            $this->inApp->notifyUser(
                $user,
                self::EVENT_KEY,
                'homework',
                __('assignment.notif_published_title'),
                __('assignment.notif_published_message', [
                    'subject' => $payload['Subject'],
                    'class' => $payload['ClassName'],
                    'deadline' => $payload['Deadline'],
                ]),
                route('assignments.index'),
                $institutionId,
                'fa-book',
                ['assignment_id' => $assignment->id]
            );
        }

        $parent = $student->parent;
        $phoneField = ($student->primary_guardian ?? 'father') . '_phone';
        $phone = $parent?->{$phoneField}
            ?? $parent?->father_phone
            ?? $parent?->mother_phone
            ?? $parent?->guardian_phone
            ?? $student->mobile_number;

        $email = $parent?->guardian_email ?? $student->email;

        $related = [
            'related_type' => Assignment::class,
            'related_id' => $assignment->id,
            'event_key' => self::EVENT_KEY,
        ];

        $sent = 0;

        if ($phone) {
            $phoneKey = preg_replace('/\D+/', '', (string) $phone) ?: (string) $phone;

            if (! isset($seenPhones[$phoneKey])) {
                $seenPhones[$phoneKey] = true;
                $delivered = false;

                // WhatsApp first — it is the channel parents actually read.
                if ($this->notifications->channelEnabled($institutionId, self::EVENT_KEY, 'whatsapp')) {
                    $result = $this->notifications->sendNotificationEvent(
                        self::EVENT_KEY,
                        $phone,
                        $payload,
                        $institutionId,
                        'whatsapp',
                        $related
                    );
                    $delivered = ! empty($result['success']);
                }

                if (! $delivered && $this->notifications->channelEnabled($institutionId, self::EVENT_KEY, 'sms')) {
                    $result = $this->notifications->sendNotificationEvent(
                        self::EVENT_KEY,
                        $phone,
                        $payload,
                        $institutionId,
                        'sms',
                        $related
                    );
                    $delivered = ! empty($result['success']);
                }

                if ($delivered) {
                    $sent++;
                }
            }
        }

        if ($email && ! isset($seenEmails[strtolower($email)])) {
            $seenEmails[strtolower($email)] = true;
            $this->notifications->sendEmailTemplate(self::EVENT_KEY, $email, $payload, $institutionId);
        }

        return $sent;
    }

    protected function classLabel(Assignment $assignment): string
    {
        $section = $assignment->classSection;

        if (! $section) {
            return '—';
        }

        return function_exists('class_section_label')
            ? class_section_label($section)
            : trim(($section->gradeLevel->name ?? '') . ' ' . $section->name);
    }
}
