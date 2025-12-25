<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformInvoice extends Model
{
    protected $fillable = [
        'invoice_number', 'institution_id', 'subscription_id', 
        'invoice_date', 'due_date', 'total_amount', 'status'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}