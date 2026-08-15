<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'class_section_id',
        'subject_id',
        'teacher_id',
        'title',
        'description',
        'deadline',
        'file_path',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'published_at',
        'parents_notified_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'parents_notified_at' => 'datetime',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Staff::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Homework that students, parents and the chatbot are allowed to see.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
