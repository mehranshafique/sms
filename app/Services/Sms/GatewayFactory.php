<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use InvalidArgumentException;

class GatewayFactory
{
    /**
     * Create gateway instance.
     * @param string $provider
     * @param int|null $institutionId Context for loading credentials.
     */
    public static function create(string $provider, int $institutionId = null): SmsGatewayInterface
    {
        switch (strtolower($provider)) {
            case 'infobip':
                return new InfobipService($institutionId);
            case 'mobishastra':
                return new MobishastraService($institutionId);
            case 'meta':
                // Ensure class exists before instantiating to avoid crash if file not created yet
                if (class_exists(\App\Services\Sms\MetaWhatsAppService::class)) {
                    return new \App\Services\Sms\MetaWhatsAppService($institutionId);
                }
                break;
            case 'twilio':
                if (class_exists(\App\Services\Sms\TwilioService::class)) {
                    return new \App\Services\Sms\TwilioService($institutionId);
                }
                break;
            case 'signalwire':
                if (class_exists(\App\Services\Sms\SignalWireService::class)) {
                    return new \App\Services\Sms\SignalWireService($institutionId);
                }
                break;
        }

        // Fallback to Mobishastra if unknown or class missing
        return new MobishastraService($institutionId);
    }
}