<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id', 'campus_id', 'grade_level_id', 'class_section_id',
        'admission_number', 'roll_number', 'admission_date',
        'first_name', 'last_name', 'gender', 'dob', 'blood_group', 'religion', 'category',
        'mobile_number', 'email', 'current_address', 'permanent_address',
        'father_name', 'father_phone', 'father_occupation',
        'mother_name', 'mother_phone', 'mother_occupation',
        'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_email',
        'student_photo', 'status'
    ];

    protected $dates = ['admission_date', 'dob'];

    protected $casts = [
        'admission_date' => 'date',
        'dob' => 'date',
    ];

    /**
     * Boot function to handle auto-generation of Admission Number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->admission_number)) {
                $student->admission_number = self::generateAdmissionNumber($student->institution_id);
            }
        });
    }

    /**
     * Generate Format: [InstitutionID][YY][XXXXX]
     * e.g., 12500001 (Inst: 1, Year: 25, Seq: 00001)
     */
    public static function generateAdmissionNumber($institutionId)
    {
        $year = Carbon::now()->format('y'); // 25
        $prefix = $institutionId . $year;   // 125
        
        // Find last student in this institution for this year
        // We look for admission numbers starting with this prefix
        $lastStudent = self::where('institution_id', $institutionId)
            ->where('admission_number', 'like', $prefix . '%')
            ->orderBy('admission_number', 'desc')
            ->first();

        if ($lastStudent) {
            // Extract sequence
            $lastSequence = intval(substr($lastStudent->admission_number, strlen($prefix)));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        // Pad with 5 zeros
        return $prefix . str_pad($newSequence, 5, '0', STR_PAD_LEFT);
    }

    // --- Relationships ---

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    // Accessor for Full Name
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    /**
     * Relationship: A Student has many Enrollments (History of classes/sessions)
     */
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}