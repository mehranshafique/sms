<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentDebt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'student_id',
        'origin_session_id',
        'amount',
        'description',
        'status' // 'unpaid', 'partial', 'paid'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function originSession()
    {
        return $this->belongsTo(AcademicSession::class, 'origin_session_id');
    }
}