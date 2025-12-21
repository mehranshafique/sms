<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'is_active',
    ];

    /**
     * Relationship: A Stream belongs to an Institution.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Relationship: A Stream can have many Class Sections.
     * This is how it connects to Grades indirectly (Grade -> ClassSection <- Stream).
     */
    public function classSections()
    {
        return $this->hasMany(ClassSection::class);
    }

    /**
     * Relationship: Get Grade Levels associated with this stream.
     * Derived via the ClassSection relationship.
     */
    public function gradeLevels()
    {
        return $this->hasManyThrough(
            GradeLevel::class,
            ClassSection::class,
            'stream_id', // Foreign key on class_sections table...
            'id', // Foreign key on grade_levels table...
            'id', // Local key on streams table...
            'grade_level_id' // Foreign key on class_sections table...
        )->distinct();
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}