<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'election_position_id',
        'candidate_id',
        'voter_id', // Student ID
        'voted_at',
        'device_id', // Optional: for tracking/security
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    /**
     * Relationship: The election this vote belongs to.
     */
    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Relationship: The position this vote is for.
     */
    public function position()
    {
        return $this->belongsTo(ElectionPosition::class, 'election_position_id');
    }

    /**
     * Relationship: The candidate voted for.
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Relationship: The student who cast the vote.
     */
    public function voter()
    {
        return $this->belongsTo(Student::class, 'voter_id');
    }
}