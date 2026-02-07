<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AcademicType; // Import the Enum

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'program_id', // Ensure this is fillable
        'name',
        'code',
        'order_index',
        'education_cycle',
    ];

    /**
     * Cast the education_cycle to the AcademicType Enum.
     */
    protected $casts = [
        'education_cycle' => AcademicType::class,
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

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
    // --- FIX: Added Program Relationship ---
    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    public function classSections()
    {
        return $this->hasMany(ClassSection::class);
    }
    
    public function academicUnits()
    {
        return $this->hasMany(AcademicUnit::class);
    }
    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index', 'asc');
    }
}