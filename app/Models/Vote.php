<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    /** Legacy V1 linked votes (renamed after Voting V2 migration). */
    protected $table = 'votes_legacy_v1';

    public $timestamps = false;

    protected $fillable = [
        'election_id',
        'election_position_id',
        'candidate_id',
        'voter_id',
        'voted_at',
        'device_id',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function position()
    {
        return $this->belongsTo(ElectionPosition::class, 'election_position_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function voter()
    {
        return $this->belongsTo(Student::class, 'voter_id');
    }
}
