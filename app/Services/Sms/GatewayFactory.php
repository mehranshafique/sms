<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use InvalidArgumentException;

class GatewayFactory
{
    public static function create(string $provider): SmsGatewayInterface
    {
        switch (strtolower($provider)) {
            case 'infobip':
                return app(InfobipService::class);
            case 'mobishastra':
                return app(MobishastraService::class);
            // Future providers:
            // case 'twilio': return app(TwilioService::class);
            default:
                throw new InvalidArgumentException("Unsupported SMS provider: {$provider}");
        }
    }
}