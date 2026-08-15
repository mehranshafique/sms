<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InfirmaryVisit extends Model
{
    use HasFactory, SoftDeletes;

    public const OUTCOMES = [
        'returned_to_class',
        'rested',
        'sent_home',
        'referred_hospital',
        'other',
    ];

    protected $fillable = [
        'institution_id',
        'student_id',
        'academic_session_id',
        'visited_at',
        'reason',
        'observation',
        'action_taken',
        'temperature',
        'blood_pressure',
        'outcome',
        'parent_informed',
        'parent_informed_at',
        'recorded_by',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'parent_informed' => 'boolean',
        'parent_informed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function outcomeLabel(): string
    {
        return __('medical.outcome_' . $this->outcome);
    }

    public function outcomeBadgeClass(): string
    {
        return match ($this->outcome) {
            'sent_home' => 'warning',
            'referred_hospital' => 'danger',
            'rested' => 'info',
            default => 'success',
        };
    }
}
