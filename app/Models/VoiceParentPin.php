<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceParentPin extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'parent_id',
        'pin_hash',
        'failed_attempts',
        'locked_until',
        'last_used_at',
        'set_by',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['pin_hash'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(StudentParent::class, 'parent_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
