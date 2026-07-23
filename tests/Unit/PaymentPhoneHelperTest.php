<?php

use App\Services\PaymentGateways\PaymentPhoneHelper;

it('preserves full congo international numbers', function () {
    expect(PaymentPhoneHelper::toMsisdn('+243899763122'))->toBe('243899763122')
        ->and(PaymentPhoneHelper::toMsisdn('243899763122'))->toBe('243899763122');
});

it('preserves pakistan international numbers without forcing 243', function () {
    expect(PaymentPhoneHelper::toMsisdn('+923033420068'))->toBe('923033420068')
        ->and(PaymentPhoneHelper::toMsisdn('923033420068'))->toBe('923033420068');
});

it('applies default congo code only for local national numbers', function () {
    expect(PaymentPhoneHelper::toMsisdn('0899763122'))->toBe('243899763122')
        ->and(PaymentPhoneHelper::toMsisdn('899763122'))->toBe('243899763122');
});
