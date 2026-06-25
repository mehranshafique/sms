<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DisciplinaryRecord extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'late_arrival',
        'unexcused_absence',
        'warning',
        'suspension',
        'parent_meeting',
        'parent_summoned',
        'other',
    ];

    public const SEVERITIES = ['minor', 'moderate', 'major'];
    public const STATUSES = ['active', 'resolved', 'cancelled'];

    protected $fillable = [
        'institution_id',
        'student_id',
        'academic_session_id',
        'recorded_by',
        'reference_no',
        'incident_type',
        'severity',
        'status',
        'title',
        'description',
        'action_taken',
        'incident_date',
        'notify_parents',
        'parents_notified_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'notify_parents' => 'boolean',
        'parents_notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (empty($record->reference_no)) {
                $record->reference_no = 'DIS-' . strtoupper(Str::random(8));
            }
        });
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function typeLabel(): string
    {
        return __('discipline.type_' . $this->incident_type);
    }

    public function severityLabel(): string
    {
        return __('discipline.severity_' . $this->severity);
    }

    public function statusLabel(): string
    {
        return __('discipline.status_' . $this->status);
    }
}
