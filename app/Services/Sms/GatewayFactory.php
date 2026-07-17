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
     * @param int|null $fallbackInstitutionId
     * @param string $channel sms|whatsapp — used for safe fallback when provider is unknown
     */
    public static function create(
        string $provider,
        ?int $institutionId = null,
        ?int $fallbackInstitutionId = null,
        string $channel = 'sms'
    ): SmsGatewayInterface {
        switch (strtolower($provider)) {
            case 'infobip':
                return new InfobipService($institutionId);
            case 'mobishastra':
                return new MobishastraService($institutionId);
            case 'meta':
                if (class_exists(MetaWhatsAppService::class)) {
                    return new MetaWhatsAppService($institutionId, $fallbackInstitutionId);
                }
                break;
            case 'twilio':
                if (class_exists(TwilioService::class)) {
                    return new TwilioService($institutionId);
                }
                break;
            case 'signalwire':
                if (class_exists(SignalWireService::class)) {
                    return new SignalWireService($institutionId);
                }
                break;
        }

        // Never fall back to Mobishastra for WhatsApp (it cannot send WA).
        if (strtolower($channel) === 'whatsapp' && class_exists(MetaWhatsAppService::class)) {
            return new MetaWhatsAppService($institutionId, $fallbackInstitutionId);
        }

        return new MobishastraService($institutionId);
    }
}
