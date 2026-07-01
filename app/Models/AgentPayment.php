<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentPayment extends Model
{
    protected $fillable = [
        'user_id',
        'agent_payment_period_id',
        'amount',
        'status',
        'paid_at',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function period()
    {
        return $this->belongsTo(AgentPaymentPeriod::class, 'agent_payment_period_id');
    }
}
