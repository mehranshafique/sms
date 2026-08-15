<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecondaryDeliberation extends Model
{
    public const DECISION_PENDING = 'pending';
    public const DECISION_ADMITTED = 'admitted';
    public const DECISION_REPECHAGE = 'repechage';
    public const DECISION_ADJOURNED = 'adjourned';

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'class_section_id',
        'student_id',
        'failed_subjects',
        'average_percentage',
        'decision',
        'notes',
        'decided_by',
        'decided_at',
        'notified_at',
    ];

    protected $casts = [
        'failed_subjects' => 'array',
        'average_percentage' => 'float',
        'decided_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
