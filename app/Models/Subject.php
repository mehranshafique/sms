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
        'name',
        'code',
        'type',
        'credit_hours',
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
    
    // Future: Assign teachers to subjects
    // public function teachers() { ... }
}