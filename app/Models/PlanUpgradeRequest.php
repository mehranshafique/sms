<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanUpgradeRequest extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'institution_id',
        'user_id',
        'current_package_id',
        'requested_package_id',
        'message',
        'status',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'current_package_id');
    }

    public function requestedPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'requested_package_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
