<?php

namespace App\Services\PaymentGateways\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\PaymentGatewayTransaction;
use App\Services\PaymentGateways\PaymentGatewayConfigService;
use App\Services\PaymentGateways\PaymentPhoneHelper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FlutterwaveGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentGatewayConfigService $configService,
        protected ?int $institutionId
    ) {}

    public function getName(): string
    {
        return 'flutterwave';
    }

    public function initiate(Invoice $invoice, PaymentGatewayTransaction $transaction, array $context): array
    {
        $creds = $this->configService->credentials($this->institutionId, 'flutterwave');

        $paymentOptions = match ($context['method']) {
            'card' => 'card',
            'bank_transfer' => 'banktransfer',
            default => 'mobilemoneycdr',
        };

        $payload = [
            'tx_ref' => $transaction->external_id,
            'amount' => (float) $context['amount'],
            'currency' => $context['currency'],
            'redirect_url' => route('pay.gateway.return', ['gateway' => 'flutterwave', 'reference' => $transaction->external_id]),
            'payment_options' => $paymentOptions,
            'customer' => [
                'email' => $invoice->student->email ?? ('pay' . $invoice->student_id . '@school.local'),
                'phonenumber' => PaymentPhoneHelper::toMsisdn($context['payer_phone']),
                'name' => $context['payer_name'],
            ],
            'customizations' => [
                'title' => $invoice->institution->name ?? config('app.name'),
                'description' => 'Invoice ' . $invoice->invoice_number,
                'logo' => asset('images/favicon.png'),
            ],
            'meta' => [
                'invoice_id' => $invoice->id,
                'institution_id' => $invoice->institution_id,
            ],
        ];

        $response = Http::withToken($creds['secret_key'])
            ->acceptJson()
            ->post(config('payment_gateways.providers.flutterwave.api_url') . '/payments', $payload);

        $body = $response->json() ?? [];

        if (($body['status'] ?? '') !== 'success' || empty($body['data']['link'])) {
            throw new RuntimeException($body['message'] ?? 'Flutterwave initialization failed.');
        }

        return [
            'status' => 'processing',
            'gateway_reference' => (string) ($body['data']['id'] ?? $transaction->external_id),
            'checkout_url' => $body['data']['link'],
            'message' => null,
            'raw' => $body,
        ];
    }

    public function parseCallback(array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? $payload['data']['status'] ?? ''));

        $mapped = match ($status) {
            'successful', 'success', 'completed' => 'completed',
            'failed', 'cancelled' => 'failed',
            default => 'processing',
        };

        return [
            'status' => $mapped,
            'gateway_reference' => $payload['id'] ?? $payload['data']['id'] ?? $payload['tx_ref'] ?? null,
            'raw' => $payload,
        ];
    }

    public function verifyTransaction(PaymentGatewayTransaction $transaction): array
    {
        $creds = $this->configService->credentials($this->institutionId, 'flutterwave');

        $response = Http::withToken($creds['secret_key'])
            ->acceptJson()
            ->get(config('payment_gateways.providers.flutterwave.api_url') . '/transactions/verify_by_reference', [
                'tx_ref' => $transaction->external_id,
            ]);

        $body = $response->json() ?? [];

        return $this->parseCallback($body['data'] ?? $body);
    }
}
