<?php

namespace App\Services;

use App\Enums\AcademicType;
use App\Models\InstitutionSetting;

class ApplicationGradeService
{
    /**
     * Default Application scale for primary (user-requested Congolese-style letters).
     *
     * @return list<array{min: float, grade: string, label: string}>
     */
    public function primaryDefaults(): array
    {
        return [
            ['min' => 80, 'grade' => 'A', 'label' => 'Excellent'],
            ['min' => 65, 'grade' => 'B', 'label' => 'Good'],
            ['min' => 50, 'grade' => 'AB', 'label' => 'Assez Bien'],
            ['min' => 0, 'grade' => 'C', 'label' => 'Needs Improvement'],
        ];
    }

    /**
     * Legacy secondary letter scale (previously hardcoded in bulletin blades).
     *
     * @return list<array{min: float, grade: string, label: string}>
     */
    public function secondaryDefaults(): array
    {
        return [
            ['min' => 80, 'grade' => 'E', 'label' => 'Excellent'],
            ['min' => 70, 'grade' => 'TB', 'label' => 'Très Bien'],
            ['min' => 60, 'grade' => 'B', 'label' => 'Bien'],
            ['min' => 50, 'grade' => 'AB', 'label' => 'Assez Bien'],
            ['min' => 0, 'grade' => 'F', 'label' => 'Fail'],
        ];
    }

    /**
     * @return list<array{min: float, grade: string, label: string}>
     */
    public function scaleFor(?int $institutionId, ?string $cycle = null): array
    {
        $decoded = null;
        if ($institutionId) {
            $raw = InstitutionSetting::get($institutionId, 'application_scale', null);
            $decoded = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
        }

        if (is_array($decoded) && $decoded !== []) {
            $scale = collect($decoded)
                ->map(fn ($row) => [
                    'min' => (float) ($row['min'] ?? 0),
                    'grade' => (string) ($row['grade'] ?? ''),
                    'label' => (string) ($row['label'] ?? ($row['remark'] ?? '')),
                ])
                ->filter(fn ($row) => $row['grade'] !== '')
                ->sortByDesc('min')
                ->values()
                ->all();

            if ($scale !== []) {
                return $scale;
            }
        }

        $cycleValue = is_object($cycle) ? $cycle->value : (string) $cycle;
        if (in_array($cycleValue, [AcademicType::SECONDARY->value, AcademicType::VOCATIONAL->value, 'secondary', 'vocational'], true)) {
            return $this->secondaryDefaults();
        }

        return $this->primaryDefaults();
    }

    public function fromPercentage(float $percentage, ?int $institutionId = null, ?string $cycle = null): string
    {
        $scale = $this->scaleFor($institutionId, $cycle);

        foreach ($scale as $row) {
            if ($percentage >= (float) $row['min']) {
                return $row['grade'];
            }
        }

        return $scale[array_key_last($scale)]['grade'] ?? '-';
    }
}
