<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Subject;
use Illuminate\Support\Collection;

class ExamRecordSubjectBinder
{
    /**
     * Subject IDs that represent the same paper for this exam and class.
     *
     * After a school import, marks often stay on the source subject's ID
     * (or a duplicate TECHNOLOGIE row) while Enter Marks lists the new school's subject.
     *
     * @return list<int>
     */
    public function equivalentSubjectIds(int $examId, int $classSectionId, int $subjectId): array
    {
        $ids = [$subjectId];
        $subject = Subject::find($subjectId);
        if (! $subject) {
            return $ids;
        }

        $extra = ExamRecord::query()
            ->where('exam_id', $examId)
            ->where('class_section_id', $classSectionId)
            ->where('subject_id', '!=', $subjectId)
            ->with('subject')
            ->get()
            ->filter(fn (ExamRecord $record) => $this->subjectsMatch($subject, $record->subject))
            ->pluck('subject_id')
            ->unique()
            ->all();

        return array_values(array_unique(array_merge($ids, $extra)));
    }

    /**
     * Prefer the subject_id already stored on exam_records so saving does not split one paper into two columns.
     */
    public function preferredSubjectId(int $examId, int $classSectionId, int $subjectId): int
    {
        $ids = $this->equivalentSubjectIds($examId, $classSectionId, $subjectId);
        $existing = ExamRecord::query()
            ->where('exam_id', $examId)
            ->where('class_section_id', $classSectionId)
            ->whereIn('subject_id', $ids)
            ->value('subject_id');

        return $existing ? (int) $existing : $subjectId;
    }

    public function recordsFor(int $examId, int $classSectionId, int $subjectId): Collection
    {
        $ids = $this->equivalentSubjectIds($examId, $classSectionId, $subjectId);

        return ExamRecord::query()
            ->where('exam_id', $examId)
            ->where('class_section_id', $classSectionId)
            ->whereIn('subject_id', $ids)
            ->get();
    }

    /**
     * Point orphan / duplicate-subject exam marks at this school's matching subject.
     */
    public function rebindForInstitution(int $institutionId): int
    {
        $examIds = Exam::query()->where('institution_id', $institutionId)->pluck('id');
        if ($examIds->isEmpty()) {
            return 0;
        }

        $localSubjects = Subject::query()->where('institution_id', $institutionId)->get();
        $updated = 0;

        ExamRecord::query()
            ->whereIn('exam_id', $examIds)
            ->with('subject')
            ->chunkById(200, function ($records) use ($localSubjects, $institutionId, &$updated) {
                foreach ($records as $record) {
                    $subject = $record->subject;
                    if ($subject && (int) $subject->institution_id === $institutionId) {
                        continue;
                    }

                    $match = $this->findLocalSubject($localSubjects, $subject, $record);
                    if (! $match || (int) $match->id === (int) $record->subject_id) {
                        continue;
                    }

                    $duplicate = ExamRecord::query()
                        ->where('exam_id', $record->exam_id)
                        ->where('student_id', $record->student_id)
                        ->where('subject_id', $match->id)
                        ->where('id', '!=', $record->id)
                        ->exists();

                    if ($duplicate) {
                        $record->delete();
                        $updated++;
                        continue;
                    }

                    $record->subject_id = $match->id;
                    $record->save();
                    $updated++;
                }
            });

        return $updated;
    }

    private function subjectsMatch(Subject $left, ?Subject $right): bool
    {
        if (! $right) {
            return false;
        }

        $leftCode = trim((string) $left->code);
        $rightCode = trim((string) $right->code);
        if ($leftCode !== '' && $rightCode !== '' && strcasecmp($leftCode, $rightCode) === 0) {
            return true;
        }

        return strcasecmp(trim((string) $left->name), trim((string) $right->name)) === 0;
    }

    private function findLocalSubject(Collection $localSubjects, ?Subject $orphan, ExamRecord $record): ?Subject
    {
        $gradeId = ClassSection::query()->where('id', $record->class_section_id)->value('grade_level_id');
        $pool = $gradeId
            ? $localSubjects->where('grade_level_id', $gradeId)->values()
            : $localSubjects;

        if ($pool->isEmpty()) {
            $pool = $localSubjects;
        }

        if ($orphan && trim((string) $orphan->code) !== '') {
            $byCode = $pool->first(
                fn (Subject $subject) => strcasecmp(trim((string) $subject->code), trim((string) $orphan->code)) === 0
            );
            if ($byCode) {
                return $byCode;
            }
        }

        if ($orphan && trim((string) $orphan->name) !== '') {
            return $pool->first(
                fn (Subject $subject) => strcasecmp(trim((string) $subject->name), trim((string) $orphan->name)) === 0
            );
        }

        return null;
    }
}
