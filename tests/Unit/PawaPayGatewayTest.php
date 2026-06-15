<?php

use App\Services\PaymentGateways\Drivers\PawaPayGateway;
use App\Services\PaymentGateways\PaymentGatewayConfigService;

it('maps PawaPay v2 check-deposit FOUND envelope with COMPLETED data', function () {
    $gateway = new PawaPayGateway(app(PaymentGatewayConfigService::class), null);

    $result = $gateway->parseCallback([
        'status' => 'FOUND',
        'data' => [
            'depositId' => '25778da5-b58f-4ce0-a19f-fda34d0bf0e9',
            'status' => 'COMPLETED',
        ],
    ]);

    expect($result['status'])->toBe('completed');
    expect($result['gateway_reference'])->toBe('25778da5-b58f-4ce0-a19f-fda34d0bf0e9');
});

it('maps direct webhook payload with COMPLETED status', function () {
    $gateway = new PawaPayGateway(app(PaymentGatewayConfigService::class), null);

    $result = $gateway->parseCallback([
        'depositId' => 'abc-123',
        'status' => 'COMPLETED',
    ]);

    expect($result['status'])->toBe('completed');
});

it('maps NOT_FOUND envelope as failed', function () {
    $gateway = new PawaPayGateway(app(PaymentGatewayConfigService::class), null);

    $result = $gateway->parseCallback(['status' => 'NOT_FOUND']);

    expect($result['status'])->toBe('failed');
});
