<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentPaymentPeriod extends Model
{
    protected $fillable = ['label', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(AgentPayment::class);
    }
}
