<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Institute;
class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'institute_id',
        'employee_no',
        'designation',
        'department',
        'hire_date',
        'status',
    ];

    /**
     * The user account associated with this staff member.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The campus this staff member belongs to.
     */
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    /**
     * Accessor for formatted hire date (optional convenience method).
     */
    public function getFormattedHireDateAttribute()
    {
        return $this->hire_date ? $this->hire_date->format('Y-m-d') : null;
    }
}
