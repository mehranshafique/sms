<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ballot extends Model
{
    protected $fillable = [
        'ballot_uuid',
        'election_id',
        'election_position_id',
        'candidate_id',
        'cast_at',
    ];

    protected $casts = [
        'cast_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ballot $ballot) {
            if (! $ballot->ballot_uuid) {
                $ballot->ballot_uuid = (string) Str::uuid();
            }
        });
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(ElectionPosition::class, 'election_position_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
