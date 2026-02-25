<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'campus_id',
        'grade_level_id',
        'staff_id',
        'name',
        'code',
        'room_number',
        'capacity',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function classTeacher()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    // ADDED: Missing relationship to fix the AssignmentController / ExamMarkController crash
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }
}