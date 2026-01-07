<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FundRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'budget_id',
        'requested_by', // User ID
        'amount',
        'title',
        'description',
        'status', // pending, approved, rejected, disbursed
        'approved_by', // User ID
        'approved_at',
        'rejection_reason',
        'attachment_path'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}