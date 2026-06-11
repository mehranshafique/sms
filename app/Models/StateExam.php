<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StateExam extends Model
{
    protected $fillable = [
        'institution_id', 'academic_session_id', 'name', 'level',
        'exam_date', 'centre', 'status',
    ];

    protected $casts = ['exam_date' => 'date'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(StateExamCandidate::class);
    }
}
