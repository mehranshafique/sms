<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Crypt;

class MailSettingsService
{
    private const SMTP_KEYS = [
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
    ];

    /**
     * Apply institution SMTP with global (super-admin) fallback.
     */
    public function applyForInstitution(?int $institutionId): bool
    {
        $settings = $this->resolve($institutionId);

        if (empty($settings['host'])) {
            return false;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'] ?? 587,
            'mail.mailers.smtp.username' => $settings['username'] ?? null,
            'mail.mailers.smtp.password' => $settings['password'] ?? null,
            'mail.mailers.smtp.encryption' => $settings['encryption'] === 'null' ? null : ($settings['encryption'] ?? 'tls'),
            'mail.from.address' => $settings['from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $settings['from_name'] ?? config('mail.from.name'),
        ]);

        return true;
    }

    /**
     * @return array{host?:string,port?:string,username?:string,password?:string,encryption?:string,from_address?:string,from_name?:string}
     */
    public function resolve(?int $institutionId): array
    {
        $global = InstitutionSetting::whereNull('institution_id')
            ->whereIn('key', self::SMTP_KEYS)
            ->pluck('value', 'key');

        $local = $institutionId
            ? InstitutionSetting::where('institution_id', $institutionId)
                ->whereIn('key', self::SMTP_KEYS)
                ->pluck('value', 'key')
            : collect();

        $merged = $global->merge($local);

        return [
            'host' => $merged['smtp_host'] ?? null,
            'port' => $merged['smtp_port'] ?? null,
            'username' => $merged['smtp_username'] ?? null,
            'password' => $this->decryptPassword($merged['smtp_password'] ?? null),
            'encryption' => $merged['smtp_encryption'] ?? 'tls',
            'from_address' => $merged['smtp_from_address'] ?? null,
            'from_name' => $merged['smtp_from_name'] ?? null,
        ];
    }

    private function decryptPassword(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }
}
