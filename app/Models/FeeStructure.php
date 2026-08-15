<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id', 
        'academic_session_id', 
        'fee_type_id', 
        'grade_level_id',
        'class_section_id', // Added
        'name', 
        'amount', 
        'frequency',
        'payment_mode', // Added
        'installment_order', // Added
        'allocation_mode',
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function components()
    {
        return $this->hasMany(FeeComponent::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * True when this fee is split into components and payments must be spread
     * across them proportionally.
     */
    public function isProportional(): bool
    {
        return $this->allocation_mode === 'proportional';
    }

    public function componentsTotal(): float
    {
        return (float) $this->components()->sum('amount');
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }
}