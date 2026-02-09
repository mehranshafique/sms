<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPickup extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'student_id',
        'requested_by', // Added
        'token',
        'otp',
        'status', // pending, scanned, approved, rejected, expired
        'scanned_by',
        'scanned_at',
        'approved_by', // Added
        'approved_at', // Added
        'expires_at'
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}