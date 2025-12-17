<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AcademicSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | MUTATORS (Fixes MySQL Date Format Error)
    |--------------------------------------------------------------------------
    | The DatePicker submits "1 January, 2025". We must convert to "2025-01-01".
    */

    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}