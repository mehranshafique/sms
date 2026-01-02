<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'staff_id',
        'attendance_date',
        'status',
        'check_in',
        'check_out',
        'method',
        'marked_by'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime', // Use datetime to handle time format correctly with Carbon
        'check_out' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}