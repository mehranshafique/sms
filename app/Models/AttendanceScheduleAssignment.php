<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScheduleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'attendance_schedule_id',
        'grade_level_id',
        'class_section_id',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AttendanceSchedule::class, 'attendance_schedule_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }
}
