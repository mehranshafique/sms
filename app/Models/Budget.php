<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'budget_category_id',
        'period_name', // Added
        'start_date',  // Added
        'end_date',    // Added
        'allocated_amount',
        'spent_amount',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Accessor for display
    public function getPeriodLabelAttribute()
    {
        if ($this->period_name) {
            return $this->period_name;
        }
        if ($this->start_date && $this->end_date) {
            return $this->start_date->format('M Y') . ' - ' . $this->end_date->format('M Y');
        }
        return 'Annual / Global';
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function category()
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    public function fundRequests()
    {
        return $this->hasMany(FundRequest::class);
    }
}