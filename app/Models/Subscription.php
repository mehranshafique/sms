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
        // Grace period logic could be added here or in middleware
        return $this->status === 'active' && $this->end_date->isFuture();
    }
    
    /**
     * Days until expiry
     */
    public function daysLeft()
    {
        return now()->diffInDays($this->end_date, false);
    }
}