<?php

namespace App\Services;

use App\Models\InstitutionSetting;

class PaymentMethodService
{
    public const SETTING_KEY = 'payment_methods_config';

    /** @var array<string, array{type: string, mobile: bool}> */
    public const DEFINITIONS = [
        'cash' => ['type' => 'standard', 'mobile' => false],
        'bank_transfer' => ['type' => 'standard', 'mobile' => false],
        'card' => ['type' => 'standard', 'mobile' => false],
        'online' => ['type' => 'standard', 'mobile' => false],
        'orange_money' => ['type' => 'mobile', 'mobile' => true],
        'airtel_money' => ['type' => 'mobile', 'mobile' => true],
        'mpesa' => ['type' => 'mobile', 'mobile' => true],
        'vodacom' => ['type' => 'mobile', 'mobile' => true],
    ];

    public function defaultConfig(): array
    {
        $methods = [];
        foreach (array_keys(self::DEFINITIONS) as $key) {
            $methods[$key] = [
                'enabled' => in_array($key, ['cash', 'bank_transfer', 'orange_money', 'airtel_money'], true),
                'merchant_code' => '',
                'account_name' => '',
                'account_number' => '',
                'bank_name' => '',
                'instructions' => '',
            ];
        }

        return [
            'online_payments_enabled' => true,
            'methods' => $methods,
        ];
    }

    public function getConfig(?int $institutionId): array
    {
        $stored = InstitutionSetting::get($institutionId, self::SETTING_KEY);
        $decoded = is_string($stored) ? json_decode($stored, true) : (is_array($stored) ? $stored : null);

        if (!is_array($decoded)) {
            return $this->defaultConfig();
        }

        return $this->mergeWithDefaults($decoded);
    }

    public function saveConfig(?int $institutionId, array $input): void
    {
        $config = $this->defaultConfig();
        $config['online_payments_enabled'] = !empty($input['online_payments_enabled']);

        foreach (array_keys(self::DEFINITIONS) as $key) {
            $row = $input['methods'][$key] ?? [];
            $config['methods'][$key] = [
                'enabled' => !empty($row['enabled']),
                'merchant_code' => trim((string) ($row['merchant_code'] ?? '')),
                'account_name' => trim((string) ($row['account_name'] ?? '')),
                'account_number' => trim((string) ($row['account_number'] ?? '')),
                'bank_name' => trim((string) ($row['bank_name'] ?? '')),
                'instructions' => trim((string) ($row['instructions'] ?? '')),
            ];
        }

        InstitutionSetting::set(
            $institutionId,
            self::SETTING_KEY,
            json_encode($config),
            'finance'
        );
    }

    public function isOnlineEnabled(?int $institutionId): bool
    {
        $config = $this->getConfig($institutionId);

        return !empty($config['online_payments_enabled']);
    }

    /** @return array<string, array<string, mixed>> */
    public function getEnabledMethods(?int $institutionId): array
    {
        $config = $this->getConfig($institutionId);
        $enabled = [];

        foreach ($config['methods'] as $key => $row) {
            if (!empty($row['enabled']) && isset(self::DEFINITIONS[$key])) {
                $enabled[$key] = array_merge($row, self::DEFINITIONS[$key]);
            }
        }

        return $enabled;
    }

    public function isMethodEnabled(?int $institutionId, string $method): bool
    {
        return array_key_exists($method, $this->getEnabledMethods($institutionId));
    }

    public function isMobileMethod(string $method): bool
    {
        return (self::DEFINITIONS[$method]['mobile'] ?? false) === true;
    }

    /** @return list<string> */
    public function enabledMethodKeys(?int $institutionId): array
    {
        return array_keys($this->getEnabledMethods($institutionId));
    }

    public function label(string $method): string
    {
        $key = 'payment.' . $method;

        return __($key) !== $key ? __($key) : ucwords(str_replace('_', ' ', $method));
    }

    private function mergeWithDefaults(array $stored): array
    {
        $defaults = $this->defaultConfig();
        $defaults['online_payments_enabled'] = $stored['online_payments_enabled'] ?? $defaults['online_payments_enabled'];

        foreach (array_keys(self::DEFINITIONS) as $key) {
            $defaults['methods'][$key] = array_merge(
                $defaults['methods'][$key],
                is_array($stored['methods'][$key] ?? null) ? $stored['methods'][$key] : []
            );
        }

        return $defaults;
    }
}
