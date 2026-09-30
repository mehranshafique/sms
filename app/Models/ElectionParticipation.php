<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionParticipation extends Model
{
    protected $fillable = [
        'election_id',
        'election_voter_id',
        'participated_at',
        'device_id',
    ];

    protected $casts = [
        'participated_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(ElectionVoter::class, 'election_voter_id');
    }
}
