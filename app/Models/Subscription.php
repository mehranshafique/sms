<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'institution_id', 'package_id', 'start_date', 'end_date', 
        'trial_ends_at', 'status', 'price_paid', 'payment_method', 
        'transaction_reference', 'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'trial_ends_at' => 'date',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function invoices()
    {
        return $this->hasMany(PlatformInvoice::class);
    }

    /**
     * Check if subscription allows access
     */
    public function isValid()
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || $this->status === 'cancelled'
            || $this->end_date->endOfDay()->isPast();
    }

    public function displayStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        return $this->status;
    }

    /**
     * Days until expiry (whole days, never negative when displayed as "left")
     */
    public function daysLeft(): int
    {
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    /**
     * Resolve the canonical subscription row for an institution.
     */
    public static function latestForInstitution(int $institutionId): ?self
    {
        return static::where('institution_id', $institutionId)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending_payment' THEN 1 WHEN 'expired' THEN 2 ELSE 3 END")
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();
    }
}