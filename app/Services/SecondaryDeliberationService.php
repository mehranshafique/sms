<?php

namespace App\Services;

use App\Enums\AcademicType;
use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ExamSchedule;
use App\Models\InstitutionSetting;
use App\Models\SecondaryDeliberation;
use App\Models\StudentEnrollment;
use App\Jobs\NotifyResitParentsJob;

class SecondaryDeliberationService
{
    public function __construct(protected AcademicCycleService $cycleService)
    {
    }

    /**
     * Build/refresh the year-end failing-subject list for semester-cycle classes.
     */
    public function generate(int $institutionId, int $sessionId): int
    {
        $threshold = (float) InstitutionSetting::get($institutionId, 'resit_pass_percentage', 50);
        $keys = $this->cycleService->periodKeysForTerm(AcademicType::SECONDARY->value, 2);
        $categories = [$keys['pA'], $keys['pB'], $keys['examCat']];

        $sections = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (ClassSection $section) => $this->cycleService->usesSemesterModel($this->cycleService->resolveCycle($section)));

        $count = 0;
        $seenStudentIds = [];

        foreach ($sections as $section) {
            $enrollments = StudentEnrollment::with('student')
                ->where('class_section_id', $section->id)
                ->where('academic_session_id', $sessionId)
                ->where('status', 'active')
                ->get();

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                if (! $student) {
                    continue;
                }

                $failing = $this->failingSubjects(
                    (int) $student->id,
                    (int) $enrollment->class_section_id,
                    $sessionId,
                    $categories,
                    $threshold
                );

                if ($failing === []) {
                    continue;
                }

                $seenStudentIds[] = (int) $student->id;
                $average = $this->termPercentage(
                    (int) $student->id,
                    (int) $enrollment->class_section_id,
                    $sessionId,
                    $categories
                );

                $existing = SecondaryDeliberation::where('academic_session_id', $sessionId)
                    ->where('student_id', $student->id)
                    ->first();

                SecondaryDeliberation::updateOrCreate(
                    [
                        'academic_session_id' => $sessionId,
                        'student_id' => $student->id,
                    ],
                    [
                        'institution_id' => $institutionId,
                        'class_section_id' => $enrollment->class_section_id,
                        'failed_subjects' => $failing,
                        'average_percentage' => $average,
                        'decision' => $existing?->decision ?? SecondaryDeliberation::DECISION_PENDING,
                    ]
                );
                $count++;
            }
        }

        SecondaryDeliberation::where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->where('decision', SecondaryDeliberation::DECISION_PENDING)
            ->whereNotIn('student_id', $seenStudentIds ?: [0])
            ->delete();

        return $count;
    }

    /**
     * @param  array<int, array{id: int, decision: string, notes?: string}>  $decisions
     */
    public function saveDecisions(array $decisions, int $userId): int
    {
        $allowed = [
            SecondaryDeliberation::DECISION_PENDING,
            SecondaryDeliberation::DECISION_ADMITTED,
            SecondaryDeliberation::DECISION_REPECHAGE,
            SecondaryDeliberation::DECISION_ADJOURNED,
        ];
        $updated = 0;

        foreach ($decisions as $row) {
            $id = (int) ($row['id'] ?? 0);
            $decision = (string) ($row['decision'] ?? '');
            if ($id < 1 || ! in_array($decision, $allowed, true)) {
                continue;
            }

            $model = SecondaryDeliberation::find($id);
            if (! $model) {
                continue;
            }

            $model->decision = $decision;
            $model->notes = $row['notes'] ?? $model->notes;
            if ($decision !== SecondaryDeliberation::DECISION_PENDING) {
                $model->decided_by = $userId;
                $model->decided_at = now();
            } else {
                $model->decided_by = null;
                $model->decided_at = null;
            }
            $model->save();
            $updated++;
        }

        return $updated;
    }

    public function confirmAndNotify(int $institutionId, int $sessionId): int
    {
        $ready = SecondaryDeliberation::where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->where('decision', '!=', SecondaryDeliberation::DECISION_PENDING)
            ->whereNull('notified_at')
            ->count();

        if ($ready > 0) {
            NotifyResitParentsJob::dispatch($institutionId, $sessionId);
        }

        return $ready;
    }

    /**
     * @param  list<string>  $categories
     * @return list<string>
     */
    public function failingSubjects(
        int $studentId,
        int $classSectionId,
        int $sessionId,
        array $categories,
        float $threshold
    ): array {
        $bySubject = $this->subjectTotals($studentId, $classSectionId, $sessionId, $categories);
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

    /**
     * @param  list<string>  $categories
     */
    public function termPercentage(int $studentId, int $classSectionId, int $sessionId, array $categories): float
    {
        $bySubject = $this->subjectTotals($studentId, $classSectionId, $sessionId, $categories);
        $obtained = 0.0;
        $max = 0.0;
        foreach ($bySubject as $row) {
            $obtained += $row['obtained'];
            $max += $row['max'];
        }

        return $max > 0 ? round(($obtained / $max) * 100, 2) : 0.0;
    }

    /**
     * @param  list<string>  $categories
     * @return array<int, array{name: string, obtained: float, max: float}>
     */
    public function subjectTotals(int $studentId, int $classSectionId, int $sessionId, array $categories): array
    {
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

        $examCats = Exam::whereIn('id', $schedules->pluck('exam_id'))->pluck('category', 'id');
        $maxMap = [];
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

        return $bySubject;
    }
}
