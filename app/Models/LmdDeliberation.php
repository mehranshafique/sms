<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmdDeliberation extends Model
{
    protected $fillable = [
        'institution_id', 'academic_session_id', 'student_id', 'semester',
        'average', 'mention', 'decision', 'notes', 'status',
        'validated_by', 'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
