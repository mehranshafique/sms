<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'start_year',
        'end_year',
        'status',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'start_year' => 'integer',
        'end_year'   => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function institution()
    {
        return $this->belongsTo(Institute::class, 'institution_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    // Get only active sessions
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Get current session for an institute
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
