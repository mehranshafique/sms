<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'class_section_id',
        'student_id',
        'attendance_date',
        'status',
        'remarks',
        'marked_by'
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }
}