<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    protected $fillable = [
        'institution_id', 'invoice_id', 'gateway', 'external_id', 'gateway_reference',
        'amount', 'currency', 'method', 'payer_name', 'payer_phone', 'status',
        'checkout_url', 'payment_id', 'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
