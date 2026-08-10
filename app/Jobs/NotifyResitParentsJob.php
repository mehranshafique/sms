<?php

namespace App\Jobs;

use App\Enums\AcademicType;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ExamSchedule;
use App\Models\InstitutionSetting;
use App\Models\StudentEnrollment;
use App\Services\AcademicCycleService;
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

    public function __construct(public int $examId)
    {
    }

    public function handle(NotificationService $notifications, AcademicCycleService $cycleService): void
    {
        $exam = Exam::with(['institution', 'academicSession'])->find($this->examId);
        if (! $exam || $exam->category !== 'semester_exam_2') {
            return;
        }

        $threshold = (float) InstitutionSetting::get($exam->institution_id, 'resit_pass_percentage', 50);
        $eventKey = 'resit_notification';

        $studentIds = ExamRecord::where('exam_id', $exam->id)->distinct()->pluck('student_id');
        if ($studentIds->isEmpty()) {
            return;
        }

        $keys = $cycleService->periodKeysForTerm(AcademicType::SECONDARY->value, 2);
        $categories = [$keys['pA'], $keys['pB'], $keys['examCat']];

        foreach ($studentIds as $studentId) {
            try {
                $enrollment = StudentEnrollment::with(['student.parent', 'classSection.gradeLevel', 'academicSession'])
                    ->where('student_id', $studentId)
                    ->where('academic_session_id', $exam->academic_session_id)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if (! $enrollment?->student) {
                    continue;
                }

                $cycle = $cycleService->resolveCycle($enrollment);
                if (! $cycleService->usesSemesterModel($cycle)) {
                    continue;
                }

                $failing = $this->failingSubjects(
                    (int) $studentId,
                    (int) $enrollment->class_section_id,
                    (int) $exam->academic_session_id,
                    $categories,
                    $threshold
                );

                if ($failing === []) {
                    continue;
                }

                $student = $enrollment->student;
                $parent = $student->parent;
                $phone = $parent?->father_phone
                    ?? $parent?->mother_phone
                    ?? $parent?->guardian_phone
                    ?? $student->mobile_number;

                if (! $phone) {
                    continue;
                }

                $payload = [
                    'ParentName' => $parent?->full_name ?? $parent?->father_name ?? '',
                    'StudentName' => $student->full_name ?? trim($student->first_name . ' ' . $student->last_name),
                    'ClassName' => class_section_label($enrollment->classSection),
                    'ResitSubjects' => implode(', ', $failing),
                    'SchoolName' => $exam->institution?->name ?? config('app.name'),
                    'AcademicYear' => $exam->academicSession?->name ?? '',
                ];

                $related = [
                    'related_type' => Exam::class,
                    'related_id' => $exam->id,
                    'event_key' => $eventKey,
                ];

                $sent = false;
                if ($notifications->channelEnabled($exam->institution_id, $eventKey, 'whatsapp')) {
                    $wa = $notifications->sendNotificationEvent(
                        $eventKey,
                        $phone,
                        $payload,
                        $exam->institution_id,
                        'whatsapp',
                        $related
                    );
                    $sent = ! empty($wa['success']);
                }

                if (! $sent && $notifications->channelEnabled($exam->institution_id, $eventKey, 'sms')) {
                    $notifications->sendNotificationEvent(
                        $eventKey,
                        $phone,
                        $payload,
                        $exam->institution_id,
                        'sms',
                        $related
                    );
                }
            } catch (Throwable $e) {
                Log::warning('NotifyResitParentsJob student failed', [
                    'exam_id' => $this->examId,
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $categories
     * @return list<string>
     */
    private function failingSubjects(
        int $studentId,
        int $classSectionId,
        int $sessionId,
        array $categories,
        float $threshold
    ): array {
        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $studentId)
            ->whereHas('exam', function ($q) use ($sessionId, $categories) {
                $q->where('academic_session_id', $sessionId)
                    ->whereIn('category', $categories);
            })
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        $schedules = ExamSchedule::where('class_section_id', $classSectionId)
            ->whereIn('exam_id', $records->pluck('exam_id')->unique())
            ->get();

        $maxMap = [];
        foreach ($schedules as $sch) {
            $cat = $records->firstWhere('exam_id', $sch->exam_id)?->exam?->category;
            if ($cat) {
                $maxMap[$cat][$sch->subject_id] = (float) $sch->max_marks;
            }
        }

        // Also map by loading exam categories from schedule exams
        $examCats = Exam::whereIn('id', $schedules->pluck('exam_id'))->pluck('category', 'id');
        foreach ($schedules as $sch) {
            $cat = $examCats[$sch->exam_id] ?? null;
            if ($cat) {
                $maxMap[$cat][$sch->subject_id] = (float) $sch->max_marks;
            }
        }

        $bySubject = [];
        foreach ($records as $record) {
            $subId = $record->subject_id;
            $cat = $record->exam->category;
            if (! isset($bySubject[$subId])) {
                $bySubject[$subId] = [
                    'name' => $record->subject?->name ?? ('#' . $subId),
                    'obtained' => 0.0,
                    'max' => 0.0,
                ];
            }
            if (is_numeric($record->marks_obtained)) {
                $bySubject[$subId]['obtained'] += (float) $record->marks_obtained;
            }
            $bySubject[$subId]['max'] += (float) ($maxMap[$cat][$subId] ?? 0);
        }

        $failing = [];
        foreach ($bySubject as $row) {
            if ($row['max'] <= 0) {
                continue;
            }
            $pct = ($row['obtained'] / $row['max']) * 100;
            if ($pct < $threshold) {
                $failing[] = $row['name'];
            }
        }

        return $failing;
    }
}
