<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\Drivers\CinetPayGateway;
use App\Services\PaymentGateways\Drivers\FlutterwaveGateway;
use App\Services\PaymentGateways\Drivers\PawaPayGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(
        protected PaymentGatewayConfigService $configService
    ) {}

    public function driver(?int $institutionId, ?string $provider = null): PaymentGatewayInterface
    {
        $provider = $provider ?? $this->configService->activeProvider($institutionId);

        return match ($provider) {
            'pawapay' => new PawaPayGateway($this->configService, $institutionId),
            'cinetpay' => new CinetPayGateway($this->configService, $institutionId),
            'flutterwave' => new FlutterwaveGateway($this->configService, $institutionId),
            default => throw new InvalidArgumentException("Payment gateway [{$provider}] is not configured."),
        };
    }

    public function supportsMethod(?int $institutionId, string $method): bool
    {
        $provider = $this->configService->activeProvider($institutionId);

        return match ($provider) {
            'pawapay' => isset(config('payment_gateways.pawapay_providers')[$method]),
            'cinetpay' => isset(config('payment_gateways.cinetpay_channels')[$method]),
            'flutterwave' => in_array($method, ['orange_money', 'airtel_money', 'mpesa', 'vodacom', 'card', 'bank_transfer'], true),
            default => false,
        };
    }
}
