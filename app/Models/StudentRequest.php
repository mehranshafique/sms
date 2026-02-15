<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'student_id',
        'academic_session_id',
        'type',             // absence, late, sick, early_exit
        'reason',
        'start_date',
        'end_date',
        'status',           // pending, approved, rejected
        'ticket_number',
        'created_by',       // User ID of creator (Student or Admin)
        'approved_by',      // User ID of approver
        'approved_at',
        'file_path'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}