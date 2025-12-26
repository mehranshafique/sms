<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'election_position_id',
        'student_id',
        'manifesto',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function position()
    {
        return $this->belongsTo(ElectionPosition::class, 'election_position_id');
    }

    public function election()
    {
        return $this->belongsTo(Election::class);
    }
}