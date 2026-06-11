<?php

namespace App\Services\PaymentGateways\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\PaymentGatewayTransaction;
use App\Services\PaymentGateways\PaymentGatewayConfigService;
use App\Services\PaymentGateways\PaymentPhoneHelper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CinetPayGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentGatewayConfigService $configService,
        protected ?int $institutionId
    ) {}

    public function getName(): string
    {
        return 'cinetpay';
    }

    public function initiate(Invoice $invoice, PaymentGatewayTransaction $transaction, array $context): array
    {
        $creds = $this->configService->credentials($this->institutionId, 'cinetpay');
        $amount = PaymentPhoneHelper::gatewayAmount((float) $context['amount'], $context['currency'], 'cinetpay');

        $payload = [
            'apikey' => $creds['api_key'],
            'site_id' => $creds['site_id'],
            'transaction_id' => $transaction->external_id,
            'amount' => $amount,
            'currency' => $context['currency'],
            'description' => 'School fees - ' . $invoice->invoice_number,
            'notify_url' => route('webhooks.payments.cinetpay'),
            'return_url' => route('pay.gateway.return', ['gateway' => 'cinetpay', 'reference' => $transaction->external_id]),
            'channels' => 'ALL',
            'lang' => app()->getLocale() === 'fr' ? 'fr' : 'en',
            'metadata' => (string) $invoice->id,
            'customer_id' => (string) $invoice->student_id,
            'customer_name' => $context['payer_name'],
            'customer_surname' => $invoice->student->last_name ?? 'Student',
            'customer_email' => $invoice->student->email ?? ('student' . $invoice->student_id . '@school.local'),
            'customer_phone_number' => PaymentPhoneHelper::toMsisdn($context['payer_phone']),
            'customer_address' => $invoice->institution->city ?? 'Kinshasa',
            'customer_city' => $invoice->institution->city ?? 'Kinshasa',
            'customer_country' => 'CD',
            'customer_state' => 'CD',
            'customer_zip_code' => '00000',
        ];

        $response = Http::acceptJson()->post(config('payment_gateways.providers.cinetpay.checkout_url'), $payload);
        $body = $response->json() ?? [];

        if (($body['code'] ?? '') !== '201' || empty($body['data']['payment_url'])) {
            throw new RuntimeException($body['description'] ?? $body['message'] ?? 'CinetPay initialization failed.');
        }

        return [
            'status' => 'processing',
            'gateway_reference' => $body['data']['payment_token'] ?? $transaction->external_id,
            'checkout_url' => $body['data']['payment_url'],
            'message' => null,
            'raw' => $body,
        ];
    }

    public function parseCallback(array $payload): array
    {
        $status = strtoupper((string) ($payload['cpm_result'] ?? $payload['status'] ?? ''));

        $mapped = match ($status) {
            '00', 'ACCEPTED', 'COMPLETED', 'SUCCES', 'SUCCESS' => 'completed',
            'FAILED', 'REFUSED', 'CANCELLED' => 'failed',
            default => 'processing',
        };

        return [
            'status' => $mapped,
            'gateway_reference' => $payload['cpm_trans_id'] ?? $payload['transaction_id'] ?? null,
            'raw' => $payload,
        ];
    }

    public function verifyTransaction(PaymentGatewayTransaction $transaction): array
    {
        $creds = $this->configService->credentials($this->institutionId, 'cinetpay');

        $response = Http::acceptJson()->post(config('payment_gateways.providers.cinetpay.check_url'), [
            'apikey' => $creds['api_key'],
            'site_id' => $creds['site_id'],
            'transaction_id' => $transaction->external_id,
        ]);

        $body = $response->json() ?? [];
        $data = $body['data'] ?? $body;

        return $this->parseCallback(is_array($data) ? $data : []);
    }
}
