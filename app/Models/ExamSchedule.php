<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'exam_id',
        'class_section_id',
        'subject_id',
        'exam_date',
        'start_time',
        'end_time',
        'room_number',
        'max_marks',
        'pass_marks',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'start_time' => 'datetime:H:i', // Format for easy display
        'end_time' => 'datetime:H:i',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}