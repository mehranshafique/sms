<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StateExamCandidate extends Model
{
    protected $fillable = [
        'state_exam_id', 'student_id', 'candidate_number',
        'score', 'mention', 'status',
    ];

    public function stateExam(): BelongsTo
    {
        return $this->belongsTo(StateExam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
