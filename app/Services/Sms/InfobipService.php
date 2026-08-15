<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use App\Models\InstitutionSetting;
use App\Services\MessageLogService;
use App\Services\PaymentGateways\PaymentPhoneHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class InfobipService implements SmsGatewayInterface
{
    protected $baseUrl;
    protected $apiKey;
    protected $whatsappSender;
    protected $senderId;
    protected $whatsappTemplateName;
    protected $whatsappTemplateLanguage = 'en';

    public function __construct($institutionId = null, $fallbackInstitutionId = null)
    {
        // 1. Defaults
        $this->baseUrl = config('sms.infobip.base_url');
        $this->apiKey = config('sms.infobip.api_key');
        $this->whatsappSender = config('sms.infobip.whatsapp_from');
        $this->senderId = 'Digitex';

        $this->applyInfobipSettings($institutionId);

        // System (platform) Infobip credentials still need the school's approved
        // template name/language, which are saved on the institution row.
        if (is_null($institutionId) && $fallbackInstitutionId) {
            $this->overlaySchoolTemplateSettings((int) $fallbackInstitutionId);
        }
    }

    protected function applyInfobipSettings($institutionId): void
    {
        $query = InstitutionSetting::query();
        if (is_null($institutionId)) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        $settings = $query->whereIn('key', [
            'infobip_api_key', 'infobip_subdomain', 'infobip_sender_id', 'infobip_whatsapp_from',
            'infobip_whatsapp_template_name', 'infobip_whatsapp_template_language',
        ])->pluck('value', 'key');

        $this->applySettingBag($settings, overlayTemplateOnly: false);
    }

    protected function overlaySchoolTemplateSettings(int $schoolId): void
    {
        $settings = InstitutionSetting::query()
            ->where('institution_id', $schoolId)
            ->whereIn('key', [
                'infobip_whatsapp_template_name',
                'infobip_whatsapp_template_language',
                'infobip_whatsapp_from',
            ])
            ->pluck('value', 'key');

        $this->applySettingBag($settings, overlayTemplateOnly: true);
    }

    /**
     * @param  \Illuminate\Support\Collection|array<string, mixed>  $settings
     */
    protected function applySettingBag($settings, bool $overlayTemplateOnly): void
    {
        $settings = is_array($settings) ? $settings : $settings->all();

        if (! empty($settings['infobip_whatsapp_template_name'])) {
            $this->whatsappTemplateName = trim((string) $settings['infobip_whatsapp_template_name']);
        }

        if (! empty($settings['infobip_whatsapp_template_language'])) {
            $this->whatsappTemplateLanguage = trim((string) $settings['infobip_whatsapp_template_language']);
        }

        if ($overlayTemplateOnly) {
            return;
        }

        if (isset($settings['infobip_subdomain']) && $settings['infobip_subdomain'] !== '') {
            $this->baseUrl = 'https://' . $settings['infobip_subdomain'] . '.api.infobip.com';
        }

        if (isset($settings['infobip_whatsapp_from']) && $settings['infobip_whatsapp_from'] !== '') {
            $this->whatsappSender = preg_replace('/\D+/', '', $settings['infobip_whatsapp_from']) ?: $settings['infobip_whatsapp_from'];
        }

        if (isset($settings['infobip_sender_id']) && $settings['infobip_sender_id'] !== '') {
            $this->senderId = $settings['infobip_sender_id'];
        }

        if (isset($settings['infobip_api_key']) && $settings['infobip_api_key'] !== '') {
            try {
                $this->apiKey = Crypt::decryptString($settings['infobip_api_key']);
            } catch (\Exception $e) {
                $this->apiKey = $settings['infobip_api_key'];
                Log::warning('Infobip API key used without decryption: ' . $e->getMessage());
            }
        }
    }

    public function send(string $to, string $message): array
    {
        return $this->sendSms($to, $message);
    }

    public function sendSms(string $to, string $message): array
    {
        if (! $this->apiKey) {
            return ['success' => false, 'message' => __('configuration.infobip_credentials_missing')];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post(rtrim((string) $this->baseUrl, '/') . '/sms/2/text/advanced', [
                'messages' => [[
                    'destinations' => [['to' => $to]],
                    'from' => $this->senderId,
                    'text' => $message,
                ]],
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.sms_sent_success')];
            }

            $err = $response->json()['requestError']['serviceException']['text'] ?? ($response->body() ?: 'Unknown Error');
            Log::error("Infobip SMS Error: $err");

            return ['success' => false, 'message' => $err];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sendWhatsApp(string $to, string $message): array
    {
        if (! $this->apiKey) {
            return ['success' => false, 'message' => __('configuration.infobip_credentials_missing')];
        }

        $from = preg_replace('/\D+/', '', (string) $this->whatsappSender) ?: '';
        if ($from === '') {
            return ['success' => false, 'message' => __('configuration.infobip_whatsapp_from_missing')];
        }

        $msisdn = PaymentPhoneHelper::toMsisdn($to, config('sms.default_country_code', '243'));
        if ($msisdn === '' || strlen($msisdn) < 10) {
            return [
                'success' => false,
                'message' => __('configuration.whatsapp_invalid_number'),
                'msisdn' => $msisdn,
            ];
        }

        try {
            $url = rtrim((string) $this->baseUrl, '/') . '/whatsapp/1/message/text';

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, [
                'from' => $from,
                'to' => $msisdn,
                'content' => ['text' => $message],
            ]);

            $payload = $response->json() ?? [];
            $statusName = (string) (data_get($payload, 'status.name')
                ?? data_get($payload, 'status.groupName')
                ?? '');
            $statusDesc = (string) (data_get($payload, 'status.description') ?? '');
            $apiError = (string) (
                data_get($payload, 'requestError.serviceException.text')
                ?? data_get($payload, 'requestError.serviceException.messageId')
                ?? ''
            );

            $rejected = $response->failed()
                || stripos($statusName, 'REJECTED') !== false
                || stripos($statusName, 'UNDELIVERABLE') !== false;

            if ($response->successful() && ! $rejected) {
                return [
                    'success' => true,
                    'message' => __('configuration.whatsapp_sent_success'),
                    'provider_message_id' => data_get($payload, 'messageId'),
                    'msisdn' => $msisdn,
                ];
            }

            $err = $apiError !== '' ? $apiError : ($statusDesc !== '' ? $statusDesc : ($response->body() ?: 'Failed to send WhatsApp'));

            Log::error('Infobip WhatsApp Error', [
                'response' => $payload,
                'http_status' => $response->status(),
                'to' => MessageLogService::maskPhone($msisdn),
                'from' => $from,
            ]);

            if ($this->isTemplateWindowError($err, $statusName, $statusDesc)) {
                // Outside the 24h customer-service window free-form text is rejected.
                // Retry with the school's approved template when one is configured.
                if ($this->whatsappTemplateName) {
                    return $this->sendWhatsAppTemplate($msisdn, $from, $message);
                }

                $err = __('configuration.whatsapp_template_required');
            }

            return [
                'success' => false,
                'message' => $err,
                'msisdn' => $msisdn,
                'error_code' => $statusName !== '' ? $statusName : null,
            ];
        } catch (\Exception $e) {
            Log::error('Infobip WhatsApp Exception: ' . $e->getMessage(), [
                'to' => MessageLogService::maskPhone($msisdn),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'msisdn' => $msisdn,
            ];
        }
    }

    /**
     * Send an approved Infobip WhatsApp template (business-initiated messages,
     * allowed outside the 24h window). The template must be registered with a
     * single body placeholder {{1}} that receives the full message text.
     */
    protected function sendWhatsAppTemplate(string $msisdn, string $from, string $message): array
    {
        $placeholder = preg_replace("/[ \t]{4,}/", ' ', str_replace(["\r\n", "\r"], "\n", $message));
        $placeholder = preg_replace("/\n{3,}/", "\n\n", (string) $placeholder);

        $languages = array_values(array_unique(array_filter([
            $this->whatsappTemplateLanguage,
            $this->whatsappTemplateLanguage === 'en' ? 'en_US' : null,
            $this->whatsappTemplateLanguage === 'en_US' ? 'en' : null,
            $this->whatsappTemplateLanguage === 'fr' ? 'fr_FR' : null,
            $this->whatsappTemplateLanguage === 'fr_FR' ? 'fr' : null,
        ])));

        $last = ['success' => false, 'message' => 'Failed to send WhatsApp template', 'msisdn' => $msisdn];

        foreach ($languages as $language) {
            $last = $this->postWhatsAppTemplate($msisdn, $from, (string) $placeholder, $language);
            if (! empty($last['success'])) {
                return $last;
            }
            $hay = strtolower((string) ($last['message'] ?? ''));
            if (! str_contains($hay, 'language') && ! str_contains($hay, 'template')) {
                return $last;
            }
        }

        return $last;
    }

    protected function postWhatsAppTemplate(string $msisdn, string $from, string $placeholder, string $language): array
    {
        try {
            $url = rtrim((string) $this->baseUrl, '/') . '/whatsapp/1/message/template';

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, [
                'messages' => [[
                    'from' => $from,
                    'to' => $msisdn,
                    'content' => [
                        'templateName' => $this->whatsappTemplateName,
                        'templateData' => [
                            'body' => ['placeholders' => [$placeholder]],
                        ],
                        'language' => $language,
                    ],
                ]],
            ]);

            $payload = $response->json() ?? [];
            $first = data_get($payload, 'messages.0', []);
            $statusName = (string) (data_get($first, 'status.name') ?? '');
            $groupName = (string) (data_get($first, 'status.groupName') ?? '');

            $rejected = $response->failed()
                || stripos($groupName, 'REJECTED') !== false
                || stripos($statusName, 'REJECTED') !== false;

            if ($response->successful() && ! $rejected) {
                return [
                    'success' => true,
                    'message' => __('configuration.whatsapp_sent_success'),
                    'provider_message_id' => data_get($first, 'messageId'),
                    'msisdn' => $msisdn,
                ];
            }

            $err = (string) (data_get($first, 'status.description')
                ?? data_get($payload, 'requestError.serviceException.text')
                ?? ($response->body() ?: 'Failed to send WhatsApp template'));

            Log::error('Infobip WhatsApp Template Error', [
                'response' => $payload,
                'http_status' => $response->status(),
                'template' => $this->whatsappTemplateName,
                'language' => $language,
                'to' => MessageLogService::maskPhone($msisdn),
            ]);

            return [
                'success' => false,
                'message' => $err,
                'msisdn' => $msisdn,
                'error_code' => $statusName !== '' ? $statusName : null,
            ];
        } catch (\Exception $e) {
            Log::error('Infobip WhatsApp Template Exception: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'msisdn' => $msisdn];
        }
    }

    /**
     * Send WhatsApp Document (PDF, Doc)
     */
    public function sendWhatsAppFile(string $to, string $fileUrl, string $caption = '', string $filename = 'document.pdf'): array
    {
        $msisdn = PaymentPhoneHelper::toMsisdn($to, config('sms.default_country_code', '243'));
        $from = preg_replace('/\D+/', '', (string) $this->whatsappSender) ?: '';

        try {
            $url = rtrim((string) $this->baseUrl, '/') . '/whatsapp/1/message/document';

            $payload = [
                'from' => $from,
                'to' => $msisdn,
                'content' => [
                    'mediaUrl' => $fileUrl,
                    'caption' => $caption,
                    'filename' => $filename,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Document sent successfully'];
            }

            $err = $response->json();
            Log::error('Infobip File Error: ' . json_encode($err));

            return ['success' => false, 'message' => 'Failed to send document'];
        } catch (\Exception $e) {
            Log::error('Infobip File Exception: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp Image (JPG, PNG)
     */
    public function sendWhatsAppImage(string $to, string $imageUrl, string $caption = ''): array
    {
        $msisdn = PaymentPhoneHelper::toMsisdn($to, config('sms.default_country_code', '243'));
        $from = preg_replace('/\D+/', '', (string) $this->whatsappSender) ?: '';

        try {
            $url = rtrim((string) $this->baseUrl, '/') . '/whatsapp/1/message/image';

            $payload = [
                'from' => $from,
                'to' => $msisdn,
                'content' => [
                    'mediaUrl' => $imageUrl,
                    'caption' => $caption,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, $payload);
            Log::info('Infobip Image Response: ' . $response->body());
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Image sent successfully'];
            }

            $err = $response->json();
            Log::error('Infobip Image Error: ' . json_encode($err));

            return ['success' => false, 'message' => 'Failed to send image'];
        } catch (\Exception $e) {
            Log::error('Infobip Image Exception: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function isTemplateWindowError(string $err, string $statusName, string $statusDesc): bool
    {
        $haystack = strtolower($err . ' ' . $statusName . ' ' . $statusDesc);

        return str_contains($haystack, 'template')
            || str_contains($haystack, '24 hour')
            || str_contains($haystack, '24-hour')
            || str_contains($haystack, '24h')
            || str_contains($haystack, 'session')
            || str_contains($haystack, 'customer care')
            || str_contains($haystack, 'rejected_template');
    }
}
