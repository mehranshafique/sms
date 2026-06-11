<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\PaymentGatewayTransaction;

interface PaymentGatewayInterface
{
    public function getName(): string;

    /**
     * @param  array{payer_name: string, payer_phone: string, method: string, amount: float, currency: string}  $context
     */
    public function initiate(Invoice $invoice, PaymentGatewayTransaction $transaction, array $context): array;

    /**
     * Verify webhook/callback payload and return normalized result.
     *
     * @return array{status: string, gateway_reference?: string, raw?: array}
     */
    public function parseCallback(array $payload): array;

    public function verifyTransaction(PaymentGatewayTransaction $transaction): array;
}
