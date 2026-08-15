<?php

namespace App\Services\Voice;

use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Models\StudentParent;
use App\Services\InstitutionModuleAccessService;
use Illuminate\Support\Collection;

class VoiceIdentityService
{
    public function __construct(
        protected InstitutionModuleAccessService $moduleAccess
    ) {
    }

    public function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public function resolveInstitutionIdFromBotNumber(?string $to): ?int
    {
        $digits = $this->digits($to);
        if (strlen($digits) < 8) {
            return null;
        }

        $keys = [
            'school_whatsapp_number',
            'infobip_whatsapp_from',
            'twilio_whatsapp_from',
            'meta_whatsapp_from',
            'whatsapp_from',
        ];

        $matched = [];
        $rows = InstitutionSetting::whereIn('key', $keys)
            ->whereNotNull('institution_id')
            ->whereNotNull('value')
            ->get(['institution_id', 'value']);

        foreach ($rows as $row) {
            $valueDigits = $this->digits((string) $row->value);
            if ($valueDigits === '') {
                continue;
            }
            if ($valueDigits === $digits
                || str_ends_with($digits, $valueDigits)
                || str_ends_with($valueDigits, $digits)) {
                $matched[(int) $row->institution_id] = true;
            }
        }

        return count($matched) === 1 ? (int) array_key_first($matched) : null;
    }

    public function isVoiceEnabledForInstitution(?int $institutionId): bool
    {
        if (! $institutionId) {
            return false;
        }

        if (! $this->moduleAccess->isModuleEnabled($institutionId, 'voice_ivr')) {
            return false;
        }

        $flag = InstitutionSetting::get($institutionId, 'voice_ivr_enabled', '1');

        return (string) $flag !== '0';
    }

    public function defaultLocale(int $institutionId): string
    {
        $locale = strtolower((string) InstitutionSetting::get($institutionId, 'voice_ivr_locale_default', 'fr'));

        return str_starts_with($locale, 'en') ? 'en' : 'fr';
    }

    public function findParent(int $institutionId, string $phone): ?StudentParent
    {
        $digits = $this->digits($phone);
        if (strlen($digits) < 8) {
            return null;
        }

        $variants = array_values(array_unique(array_filter([
            $digits,
            strlen($digits) > 9 ? substr($digits, -9) : null,
            strlen($digits) > 10 ? substr($digits, -10) : null,
        ])));

        return StudentParent::query()
            ->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                    ->orWhereHas('students', fn ($sq) => $sq->where('institution_id', $institutionId));
            })
            ->where(function ($query) use ($variants) {
                foreach ($variants as $variant) {
                    $query->orWhere('father_phone', 'like', "%{$variant}%")
                        ->orWhere('mother_phone', 'like', "%{$variant}%")
                        ->orWhere('guardian_phone', 'like', "%{$variant}%");
                }
            })
            ->first();
    }

    /** @return Collection<int, Student> */
    public function childrenForParent(StudentParent $parent, int $institutionId): Collection
    {
        return $parent->students()
            ->where('institution_id', $institutionId)
            ->orderBy('first_name')
            ->get();
    }

    public function schoolName(?int $institutionId): string
    {
        if (! $institutionId) {
            return (string) config('app.name', 'Digitex');
        }

        return Institution::find($institutionId)?->name ?? (string) config('app.name', 'Digitex');
    }
}
