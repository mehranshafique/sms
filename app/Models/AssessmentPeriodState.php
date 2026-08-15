<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentPeriodState extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_REOPENED = 'reopened';

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'period_key',
        'status',
        'closes_at',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
        'revision_token',
    ];

    protected $casts = [
        'closes_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'revision_token' => 'integer',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isReopened(): bool
    {
        return $this->status === self::STATUS_REOPENED;
    }

    public function allowsMarksEntry(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_REOPENED], true);
    }

    public function isOfficial(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
