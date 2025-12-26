<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'event_key',
        'name',
        'body',
        'available_tags',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to find a template for a specific context.
     * Looks for school-specific first, then falls back to global.
     */
    public function scopeForEvent($query, $eventKey, $institutionId = null)
    {
        return $query->where('event_key', $eventKey)
                     ->where(function($q) use ($institutionId) {
                         $q->where('institution_id', $institutionId)
                           ->orWhereNull('institution_id');
                     })
                     ->orderByDesc('institution_id'); // Prioritize specific ID over null
    }
}