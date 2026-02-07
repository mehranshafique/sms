<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'program_id', // Added
        'grade_level_id',
        'name',
        'code',
        'type',
        'semester',
        'total_credits'
    ];

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    // NEW: Link to Program
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
    
    // Helper to calc total credits from subjects if dynamic
    public function calculateCredits()
    {
        return $this->subjects()->sum('credit_hours');
    }
}