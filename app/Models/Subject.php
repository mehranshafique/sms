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

    /**
     * Grades this subject is taught in (central catalogue → multi-grade assignment).
     */
    public function gradeLevels()
    {
        return $this->belongsToMany(GradeLevel::class, 'subject_grade_level')
            ->withTimestamps();
    }

    /**
     * Subjects available for a grade: legacy grade_level_id or pivot assignment.
     */
    public function scopeForGrade($query, $gradeLevelId)
    {
        $gradeLevelId = (int) $gradeLevelId;

        return $query->where(function ($q) use ($gradeLevelId) {
            $q->where('subjects.grade_level_id', $gradeLevelId)
                ->orWhereHas('gradeLevels', function ($g) use ($gradeLevelId) {
                    $g->where('grade_levels.id', $gradeLevelId);
                });
        });
    }

    /**
     * Sync pivot grades and keep legacy grade_level_id as the first grade for older code paths.
     *
     * @param  list<int>  $gradeIds
     */
    public function syncGradeLevels(array $gradeIds): void
    {
        $gradeIds = array_values(array_unique(array_filter(array_map('intval', $gradeIds))));
        $this->gradeLevels()->sync($gradeIds);
        $this->grade_level_id = $gradeIds[0] ?? null;
        $this->save();
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