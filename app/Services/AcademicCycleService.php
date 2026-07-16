<?php

namespace App\Services;

use App\Enums\AcademicType;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;

class AcademicCycleService
{
    public function resolveCycle(StudentEnrollment|ClassSection $source): string
    {
        if ($source instanceof StudentEnrollment) {
            $source->loadMissing('classSection.gradeLevel');
            $gradeLevel = $source->classSection?->gradeLevel;
        } else {
            $source->loadMissing('gradeLevel');
            $gradeLevel = $source->gradeLevel;
        }

        if (!$gradeLevel) {
            return AcademicType::PRIMARY->value;
        }

        $cycle = $gradeLevel->education_cycle;
        $value = $cycle instanceof AcademicType ? $cycle->value : (string) $cycle;

        if ($value === AcademicType::VOCATIONAL->value && $gradeLevel->program_id) {
            return AcademicType::LMD->value;
        }

        return $value ?: AcademicType::PRIMARY->value;
    }

    public function isUniversityCycle(string $cycle): bool
    {
        return in_array($cycle, [AcademicType::LMD->value, 'university', 'lmd'], true);
    }

    public function usesTrimesterModel(string $cycle): bool
    {
        return $cycle === AcademicType::PRIMARY->value;
    }

    public function usesSemesterModel(string $cycle): bool
    {
        return in_array($cycle, [
            AcademicType::SECONDARY->value,
            AcademicType::VOCATIONAL->value,
        ], true);
    }

    /**
     * @return array<int, string>
     */
    public function allowedReportScopes(string $cycle): array
    {
        if ($this->isUniversityCycle($cycle)) {
            return ['session'];
        }

        if ($this->usesTrimesterModel($cycle)) {
            return ['period', 'trimester'];
        }

        if ($this->usesSemesterModel($cycle)) {
            return ['period', 'semester'];
        }

        return ['period', 'trimester'];
    }

    /**
     * @return array<int, string>
     */
    public function allowedPeriodKeys(string $cycle): array
    {
        if ($this->usesTrimesterModel($cycle)) {
            return ['p1', 'p2', 'p3', 'p4', 'p5', 'p6'];
        }

        if ($this->usesSemesterModel($cycle)) {
            return ['p1', 'p2', 'p3', 'p4'];
        }

        return [];
    }

    /**
     * @return array{pA: string, pB: string, examCat: string}
     */
    public function periodKeysForTerm(string $cycle, int $termNumber): array
    {
        $termNumber = max(1, $termNumber);

        if ($this->usesTrimesterModel($cycle)) {
            return [
                'pA' => 'p' . (($termNumber * 2) - 1),
                'pB' => 'p' . ($termNumber * 2),
                'examCat' => "trimester_exam_{$termNumber}",
            ];
        }

        $startPeriod = ($termNumber * 2) - 1;

        return [
            'pA' => 'p' . $startPeriod,
            'pB' => 'p' . ($startPeriod + 1),
            'examCat' => "semester_exam_{$termNumber}",
        ];
    }

    /**
     * @return array<int, string>
     */
    public function categoriesForRequest(string $cycle, ?string $type, ?string $period, ?int $trimester, ?int $semester): array
    {
        if ($type === 'period' && $period) {
            return [$period];
        }

        if ($this->usesTrimesterModel($cycle) && $trimester) {
            $keys = $this->periodKeysForTerm($cycle, $trimester);

            return [$keys['pA'], $keys['pB'], $keys['examCat']];
        }

        if ($this->usesSemesterModel($cycle) && $semester) {
            $keys = $this->periodKeysForTerm($cycle, $semester);

            return [$keys['pA'], $keys['pB'], $keys['examCat']];
        }

        return [];
    }

    public function validateReportRequest(string $cycle, ?string $type, ?string $period, ?int $trimester, ?int $semester): ?string
    {
        if ($this->isUniversityCycle($cycle)) {
            return __('reports.error_university_use_transcript');
        }

        if ($type === 'period') {
            if (!$period || !in_array($period, $this->allowedPeriodKeys($cycle), true)) {
                return __('reports.error_invalid_period_for_cycle');
            }

            return null;
        }

        if ($this->usesTrimesterModel($cycle)) {
            if ($semester && !$trimester) {
                return __('reports.error_semester_not_for_primary');
            }
            if ($trimester && ($trimester < 1 || $trimester > 3)) {
                return __('reports.error_invalid_trimester');
            }

            return null;
        }

        if ($this->usesSemesterModel($cycle)) {
            if ($trimester && !$semester) {
                return __('reports.error_trimester_not_for_secondary');
            }
            if ($semester && ($semester < 1 || $semester > 2)) {
                return __('reports.error_invalid_semester');
            }

            return null;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function columnLabels(string $cycle, int $termNumber, string $mode = 'term'): array
    {
        if ($mode === 'period') {
            return [
                'subject' => __('reports.subject'),
                'score' => __('reports.cotes'),
                'max' => __('reports.max_marks'),
            ];
        }

        if ($this->usesTrimesterModel($cycle)) {
            return [
                'p1' => 'P1',
                'p2' => 'P2',
                'p_max' => __('reports.max_p'),
                'exam' => __('reports.exam_trimester', ['n' => $termNumber]),
                'exam_max' => __('reports.max_exam_trimester', ['n' => $termNumber]),
                'total' => __('reports.total_trimester', ['n' => $termNumber]),
                'total_max' => __('reports.max_trimester', ['n' => $termNumber]),
            ];
        }

        return [
            'p1' => 'P1',
            'p2' => 'P2',
            'p_max' => __('reports.max_p'),
            'exam' => __('reports.exam_semester', ['n' => $termNumber]),
            'exam_max' => __('reports.max_exam_semester', ['n' => $termNumber]),
            'total' => __('reports.total_semester', ['n' => $termNumber]),
            'total_max' => __('reports.max_semester', ['n' => $termNumber]),
        ];
    }

    public function termTitle(string $cycle, int $termNumber): string
    {
        if ($this->usesTrimesterModel($cycle)) {
            return __('reports.bulletin_trimester_title', ['n' => $termNumber]);
        }

        return __('reports.bulletin_semester_title', ['n' => $termNumber]);
    }

    /**
     * @return array<int, string>
     */
    public function examCategoriesForCycle(string $cycle): array
    {
        $categories = $this->allowedPeriodKeys($cycle);

        if ($this->usesTrimesterModel($cycle)) {
            return array_merge($categories, [
                'trimester_exam_1',
                'trimester_exam_2',
                'trimester_exam_3',
            ]);
        }

        if ($this->usesSemesterModel($cycle)) {
            return array_merge($categories, [
                'semester_exam_1',
                'semester_exam_2',
            ]);
        }

        if ($this->isUniversityCycle($cycle)) {
            return [
                'university_session_1',
                'university_session_2',
                'rattrapage_session_1',
                'rattrapage_session_2',
            ];
        }

        return $categories;
    }

    /**
     * @return array<int, string>
     */
    public function examCategoriesForInstitutionType(?string $institutionType): array
    {
        $type = $institutionType ?: 'mixed';

        return match ($type) {
            'primary' => $this->examCategoriesForCycle(AcademicType::PRIMARY->value),
            'secondary' => $this->examCategoriesForCycle(AcademicType::SECONDARY->value),
            'university' => $this->examCategoriesForCycle(AcademicType::LMD->value),
            'vocational' => array_values(array_unique(array_merge(
                $this->examCategoriesForCycle(AcademicType::VOCATIONAL->value),
                $this->examCategoriesForCycle(AcademicType::LMD->value)
            ))),
            'mixed' => array_values(array_unique(array_merge(
                $this->examCategoriesForCycle(AcademicType::PRIMARY->value),
                $this->examCategoriesForCycle(AcademicType::SECONDARY->value)
            ))),
            default => array_values(array_unique(array_merge(
                $this->examCategoriesForCycle(AcademicType::PRIMARY->value),
                $this->examCategoriesForCycle(AcademicType::SECONDARY->value)
            ))),
        };
    }

    /**
     * @return array<int, string>
     */
    public function missingExamCategoriesForSession(int $institutionId, int $sessionId, ?string $institutionType): array
    {
        $required = $this->examCategoriesForInstitutionType($institutionType);
        $existing = \App\Models\Exam::where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->pluck('category')
            ->filter()
            ->all();

        return array_values(array_diff($required, $existing));
    }

    /**
     * @return array<string, mixed>
     */
    public function scopeOptionsPayload(string $cycle): array
    {
        return [
            'cycle' => $cycle,
            'scopes' => $this->allowedReportScopes($cycle),
            'periods' => $this->allowedPeriodKeys($cycle),
            'max_trimester' => $this->usesTrimesterModel($cycle) ? 3 : 0,
            'max_semester' => $this->usesSemesterModel($cycle) ? 2 : 0,
        ];
    }
}
