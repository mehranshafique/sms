<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'department_id',
        'name',
        'code',
        'total_semesters',
        'duration_years',
        'is_active'
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function gradeLevels()
    {
        return $this->hasMany(GradeLevel::class);
    }
}