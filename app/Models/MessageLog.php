<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'institution_id',
        'channel',
        'event_key',
        'to_masked',
        'status',
        'provider',
        'provider_msg_id',
        'error',
        'credited',
        'related_type',
        'related_id',
        'created_at',
    ];

    protected $casts = [
        'credited' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function related()
    {
        return $this->morphTo();
    }
}
