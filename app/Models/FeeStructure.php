<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id', 'academic_session_id', 'fee_type_id', 'grade_level_id',
        'name', 'amount', 'frequency'
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }
}