<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Traits\LogsActivity;

class Student extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'institution_id', 
        'campus_id', 
        'grade_level_id', 
        'class_section_id',
        'parent_id', // Replaces direct parent fields
        'user_id',
        'admission_number', 
        'roll_number', 
        'admission_date',
        'first_name', 
        'last_name', 
        'gender', 
        'dob', 
        'blood_group',
        'place_of_birth', 
        'religion', 
        'category',
        'post_name', // Ensure this is present
        'mobile_number', 
        'email', 
        'current_address', 
        'permanent_address',
        'country', 
        'state', 
        'city', 
        'avenue',
        'student_photo', 
        'status',
        'payment_mode',
        'nfc_tag_uid', 
        'qr_code_token'
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

    public static function generateAdmissionNumber($institutionId)
    {
        $year = Carbon::now()->format('y'); 
        $prefix = $institutionId . $year;   
        
        $lastStudent = self::where('institution_id', $institutionId)
            ->where('admission_number', 'like', $prefix . '%')
            ->orderBy('admission_number', 'desc')
            ->first();

        if ($lastStudent) {
            $lastSequence = intval(substr($lastStudent->admission_number, strlen($prefix)));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 5, '0', STR_PAD_LEFT);
    }

    // --- Relationships ---

    /**
     * Relationship: Student belongs to a Parent/Guardian record
     */
    public function parent()
    {
        return $this->belongsTo(StudentParent::class, 'parent_id');
    }

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

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Accessors ---

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Helpers to access parent info easily via the relationship
    public function getFatherNameAttribute()
    {
        return $this->parent->father_name ?? null;
    }
    
    public function getFatherPhoneAttribute()
    {
        return $this->parent->father_phone ?? null;
    }
    
    public function getMotherNameAttribute()
    {
        return $this->parent->mother_name ?? null;
    }
}