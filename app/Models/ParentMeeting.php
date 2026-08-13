<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentMeeting extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];
    public const SCOPES = ['individual', 'class'];

    protected $fillable = [
        'institution_id',
        'scope',
        'student_id',
        'class_section_id',
        'batch_id',
        'requested_by',
        'handled_by',
        'topic',
        'preferred_date',
        'notes',
        'staff_notes',
        'status',
        'handled_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'handled_at' => 'datetime',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isClassScope(): bool
    {
        return $this->scope === 'class';
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope ?? 'individual',
            'student_id' => $this->student_id,
            'student_name' => $this->student?->full_name,
            'class_section_id' => $this->class_section_id,
            'class_name' => $this->classSection
                ? (function_exists('class_section_label')
                    ? class_section_label($this->classSection)
                    : ($this->classSection->name ?? null))
                : null,
            'batch_id' => $this->batch_id,
            'topic' => $this->topic,
            'preferred_date' => $this->preferred_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'staff_notes' => $this->staff_notes,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'handled_by' => $this->handled_by,
            'handled_at' => $this->handled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
