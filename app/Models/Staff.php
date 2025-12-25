<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Staff extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'institution_id',
        'campus_id',
        'employee_id',
        'designation',
        'department',
        'joining_date',
        'salary',
        'gender',
        'dob',
        'qualification',
        'experience',
        'emergency_contact',
        'address',
        'status'
    ];

    protected $casts = [
        'joining_date' => 'date',
        'dob' => 'date',
    ];

    // --- Relationships ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}