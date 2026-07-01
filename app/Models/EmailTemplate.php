<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'institution_id',
        'event_key',
        'name',
        'subject',
        'body',
        'available_tags',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeForEvent($query, string $eventKey, ?int $institutionId = null)
    {
        return $query->where('event_key', $eventKey)
            ->where(function ($q) use ($institutionId) {
                $q->whereNull('institution_id');
                if ($institutionId) {
                    $q->orWhere('institution_id', $institutionId);
                }
            })
            ->orderByRaw('institution_id IS NULL ASC');
    }
}
