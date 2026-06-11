<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'invoice_id',
        'institution_id',
        'transaction_id',
        'payment_date',
        'amount',
        'method', // cash, bank_transfer, card, online, orange_money, airtel_money, mpesa, vodacom
        'source',
        'mobile_money_provider',
        'mobile_reference',
        'notes',
        'payer_name',
        'payer_phone',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}