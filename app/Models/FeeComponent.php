<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'fee_structure_id',
        'name',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Share of the parent fee this component represents, as a percentage.
     */
    public function sharePercentage(): float
    {
        $total = (float) ($this->feeStructure->amount ?? 0);

        if ($total <= 0) {
            return 0.0;
        }

        return round(((float) $this->amount / $total) * 100, 2);
    }
}
