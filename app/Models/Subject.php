<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'grade_level_id',
        'department_id', // New
        'academic_unit_id', // NEW
        'prerequisite_id', // New
        'name',
        'code',
        'type',
        'semester', // New
        'credit_hours',
        'coefficient', // NEW
        'total_marks',
        'passing_marks',
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

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    // New: Department Relationship
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    // NEW: Link to UE
    public function academicUnit()
    {
        return $this->belongsTo(AcademicUnit::class);
    }
    // New: Prerequisite Relationship
    public function prerequisite()
    {
        return $this->belongsTo(Subject::class, 'prerequisite_id');
    }
    
    // New: Subjects that require this one (Reverse Prerequisite)
    public function dependentSubjects()
    {
        return $this->hasMany(Subject::class, 'prerequisite_id');
    }
}