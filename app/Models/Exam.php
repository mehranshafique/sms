<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'name',
        'category', // NEW: p1, p2, p3, p4, p5, p6, trimester_exam, semester_exam
        'start_date',
        'end_date',
        'status',
        'description',
        'finalized_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function records()
    {
        return $this->hasMany(ExamRecord::class);
    }
}