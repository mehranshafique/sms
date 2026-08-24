<?php

namespace App\Services;

use App\Enums\AcademicType;
use App\Models\InstitutionSetting;

class ReportAuthorityService
{
    public const CYCLE_PRIMARY = 'primary';
    public const CYCLE_SECONDARY = 'secondary';

    /**
     * @return array{title: string, name: string, signature: ?string, cycle: string}
     */
    public function forCycle(int $institutionId, ?string $cycle): array
    {
        $key = $this->normalizeCycle($cycle);

        if ($key === self::CYCLE_SECONDARY) {
            return [
                'cycle' => self::CYCLE_SECONDARY,
                'title' => trim((string) InstitutionSetting::get(
                    $institutionId,
                    'report_authority_secondary_title',
                    __('reports.authority_secondary_title')
                )) ?: __('reports.authority_secondary_title'),
                'name' => trim((string) InstitutionSetting::get(
                    $institutionId,
                    'report_authority_secondary_name',
                    ''
                )),
                'signature' => InstitutionSetting::get($institutionId, 'report_authority_secondary_signature', '') ?: null,
            ];
        }

        return [
            'cycle' => self::CYCLE_PRIMARY,
            'title' => trim((string) InstitutionSetting::get(
                $institutionId,
                'report_authority_primary_title',
                __('reports.authority_primary_title')
            )) ?: __('reports.authority_primary_title'),
            'name' => trim((string) InstitutionSetting::get(
                $institutionId,
                'report_authority_primary_name',
                ''
            )),
            'signature' => InstitutionSetting::get($institutionId, 'report_authority_primary_signature', '') ?: null,
        ];
    }

    public function normalizeCycle(?string $cycle): string
    {
        $value = is_object($cycle) && property_exists($cycle, 'value')
            ? (string) $cycle->value
            : strtolower(trim((string) $cycle));

        if (in_array($value, [
            AcademicType::SECONDARY->value,
            'secondary',
            'secondaire',
        ], true)) {
            return self::CYCLE_SECONDARY;
        }

        return self::CYCLE_PRIMARY;
    }
}
