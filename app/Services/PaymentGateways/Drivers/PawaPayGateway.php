<?php

namespace App\Services\PaymentGateways\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\PaymentGatewayTransaction;
use App\Services\PaymentGateways\PaymentGatewayConfigService;
use App\Services\PaymentGateways\PaymentPhoneHelper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PawaPayGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentGatewayConfigService $configService,
        protected ?int $institutionId
    ) {}

    public function getName(): string
    {
        return 'pawapay';
    }

    public function initiate(Invoice $invoice, PaymentGatewayTransaction $transaction, array $context): array
    {
        $provider = config('payment_gateways.pawapay_providers')[$context['method']] ?? null;
        if (!$provider) {
            throw new RuntimeException(__('payment_gateway.method_not_supported'));
        }

        $creds = $this->configService->credentials($this->institutionId, 'pawapay');
        $baseUrl = $this->configService->environment($this->institutionId) === 'production'
            ? config('payment_gateways.providers.pawapay.production_url')
            : config('payment_gateways.providers.pawapay.sandbox_url');

        $phone = PaymentPhoneHelper::toMsisdn($context['payer_phone']);
        $amount = PaymentPhoneHelper::gatewayAmount((float) $context['amount'], $context['currency'], 'pawapay');

        $payload = [
            'depositId' => $transaction->external_id,
            'amount' => (string) $amount,
            'currency' => $context['currency'],
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $phone,
                    'provider' => $provider,
                ],
            ],
            'clientReferenceId' => (string) $invoice->invoice_number,
            'customerMessage' => $this->buildCustomerMessage($invoice),
        ];

        $response = Http::withToken($creds['api_token'])
            ->acceptJson()
            ->post(rtrim($baseUrl, '/') . '/v2/deposits', $payload);

        $body = $response->json() ?? [];

        if (!$response->successful()) {
            throw new RuntimeException($body['failureReason']['failureMessage'] ?? $body['message'] ?? 'PawaPay request failed.');
        }

        $status = strtolower((string) ($body['status'] ?? 'pending'));

        return [
            'status' => in_array($status, ['accepted', 'processing', 'completed'], true) ? 'processing' : 'failed',
            'gateway_reference' => $body['depositId'] ?? $transaction->external_id,
            'checkout_url' => null,
            'message' => __('payment_gateway.pawapay_confirm_phone'),
            'raw' => $body,
        ];
    }

    public function parseCallback(array $payload): array
    {
        $envelope = strtoupper((string) ($payload['status'] ?? ''));

        // GET /v2/deposits/{id} wraps the deposit: { status: FOUND|NOT_FOUND, data: {...} }
        if ($envelope === 'NOT_FOUND') {
            return [
                'status' => 'failed',
                'gateway_reference' => data_get($payload, 'data.depositId'),
                'raw' => $payload,
            ];
        }

        if ($envelope === 'FOUND' && is_array($payload['data'] ?? null)) {
            $payload = $payload['data'];
        }

        $status = strtoupper((string) ($payload['status'] ?? ''));
        $mapped = match ($status) {
            'COMPLETED', 'SUCCESS' => 'completed',
            'FAILED', 'REJECTED', 'CANCELLED' => 'failed',
            default => 'processing',
        };

        return [
            'status' => $mapped,
            'gateway_reference' => $payload['depositId'] ?? null,
            'raw' => $payload,
        ];
    }

    public function verifyTransaction(PaymentGatewayTransaction $transaction): array
    {
        $creds = $this->configService->credentials($this->institutionId, 'pawapay');
        if (empty($creds['api_token'])) {
            return ['status' => 'processing', 'gateway_reference' => $transaction->gateway_reference, 'raw' => []];
        }

        $baseUrl = $this->configService->environment($this->institutionId) === 'production'
            ? config('payment_gateways.providers.pawapay.production_url')
            : config('payment_gateways.providers.pawapay.sandbox_url');

        $response = Http::withToken($creds['api_token'])
            ->acceptJson()
            ->get(rtrim($baseUrl, '/') . '/v2/deposits/' . $transaction->external_id);

        if (!$response->successful()) {
            return [
                'status' => 'processing',
                'gateway_reference' => $transaction->gateway_reference,
                'raw' => ['http_status' => $response->status(), 'body' => $response->json()],
            ];
        }

        return $this->parseCallback($response->json() ?? []);
    }

    /**
     * PawaPay customerMessage: 4–22 chars, pattern ^[a-zA-Z0-9 ]+$ (shown on parent's MoMo SMS).
     */
    private function buildCustomerMessage(Invoice $invoice): string
    {
        $ref = preg_replace('/[^a-zA-Z0-9]/', '', (string) $invoice->invoice_number);
        $message = trim('School fees ' . $ref);

        if (strlen($message) > 22) {
            $message = substr($message, 0, 22);
        }

        return strlen($message) >= 4 ? $message : 'School fees';
    }
}
