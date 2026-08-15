<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (payment, fee component) pair: the exact slice of a payment that
 * was assigned to a fee component. Rows with a null fee_component_id represent
 * invoice lines that are not broken into components.
 */
class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'payment_id',
        'invoice_id',
        'invoice_item_id',
        'fee_structure_id',
        'fee_component_id',
        'label',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function feeComponent(): BelongsTo
    {
        return $this->belongsTo(FeeComponent::class);
    }
}
