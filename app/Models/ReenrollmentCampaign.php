<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReenrollmentCampaign extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'institution_id',
        'name',
        'from_academic_session_id',
        'to_academic_session_id',
        'min_fee_amount',
        'opens_at',
        'closes_at',
        'status',
        'notes',
        'created_by',
        'invitations_sent_at',
        'invitations_sent_count',
        'closed_at',
    ];

    protected $casts = [
        'min_fee_amount' => 'decimal:2',
        'opens_at' => 'date',
        'closes_at' => 'date',
        'invitations_sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function fromSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'from_academic_session_id');
    }

    public function toSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'to_academic_session_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(ReenrollmentConfirmation::class, 'campaign_id');
    }

    public function isOpen(): bool
    {
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        $today = now()->startOfDay();
        if ($this->opens_at && $today->lt($this->opens_at->startOfDay())) {
            return false;
        }
        if ($this->closes_at && $today->gt($this->closes_at->endOfDay())) {
            return false;
        }

        return true;
    }
}
