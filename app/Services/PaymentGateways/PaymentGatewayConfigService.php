<?php

namespace App\Services\PaymentGateways;

use App\Models\InstitutionSetting;

class PaymentGatewayConfigService
{
    public const SETTING_KEY = 'payment_gateway_config';

    public function defaultConfig(): array
    {
        return [
            'provider' => 'none',
            'environment' => config('payment_gateways.default_environment', 'sandbox'),
            'manual_proof_enabled' => true,
            'credentials' => [
                'pawapay' => ['api_token' => ''],
                'cinetpay' => ['api_key' => '', 'site_id' => ''],
                'flutterwave' => ['public_key' => '', 'secret_key' => '', 'secret_hash' => ''],
            ],
        ];
    }

    public function getConfig(?int $institutionId): array
    {
        $stored = InstitutionSetting::get($institutionId, self::SETTING_KEY);
        $decoded = is_string($stored) ? json_decode($stored, true) : (is_array($stored) ? $stored : null);

        if (!is_array($decoded)) {
            return $this->defaultConfig();
        }

        $defaults = $this->defaultConfig();
        $defaults['provider'] = $decoded['provider'] ?? $defaults['provider'];
        $defaults['environment'] = $decoded['environment'] ?? $defaults['environment'];
        $defaults['manual_proof_enabled'] = $decoded['manual_proof_enabled'] ?? $defaults['manual_proof_enabled'];

        foreach (array_keys($defaults['credentials']) as $gw) {
            $defaults['credentials'][$gw] = array_merge(
                $defaults['credentials'][$gw],
                is_array($decoded['credentials'][$gw] ?? null) ? $decoded['credentials'][$gw] : []
            );
        }

        return $defaults;
    }

    public function saveConfig(?int $institutionId, array $input): void
    {
        $config = $this->defaultConfig();
        $config['provider'] = in_array($input['provider'] ?? 'none', ['none', 'pawapay', 'cinetpay', 'flutterwave'], true)
            ? $input['provider']
            : 'none';
        $config['environment'] = ($input['environment'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox';
        $config['manual_proof_enabled'] = !empty($input['manual_proof_enabled']);

        $creds = $input['credentials'] ?? [];
        foreach (array_keys($config['credentials']) as $gw) {
            $row = $creds[$gw] ?? [];
            foreach ($config['credentials'][$gw] as $field => $default) {
                $config['credentials'][$gw][$field] = trim((string) ($row[$field] ?? $default));
            }
        }

        InstitutionSetting::set($institutionId, self::SETTING_KEY, json_encode($config), 'finance');
    }

    public function isGatewayActive(?int $institutionId): bool
    {
        $config = $this->getConfig($institutionId);

        return $config['provider'] !== 'none' && $this->hasCredentials($institutionId, $config['provider']);
    }

    public function isManualProofEnabled(?int $institutionId): bool
    {
        return !empty($this->getConfig($institutionId)['manual_proof_enabled']);
    }

    public function credentials(?int $institutionId, string $provider): array
    {
        $config = $this->getConfig($institutionId);

        return $config['credentials'][$provider] ?? [];
    }

    public function activeProvider(?int $institutionId): string
    {
        return $this->getConfig($institutionId)['provider'] ?? 'none';
    }

    public function environment(?int $institutionId): string
    {
        return $this->getConfig($institutionId)['environment'] ?? 'sandbox';
    }

    private function hasCredentials(?int $institutionId, string $provider): bool
    {
        $creds = $this->credentials($institutionId, $provider);

        return match ($provider) {
            'pawapay' => !empty($creds['api_token']),
            'cinetpay' => !empty($creds['api_key']) && !empty($creds['site_id']),
            'flutterwave' => !empty($creds['secret_key']) && !empty($creds['public_key']),
            default => false,
        };
    }
}
