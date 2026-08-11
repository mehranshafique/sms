<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreEnrollment extends Model
{
    use HasFactory;

    public const STATUS_PRE_ENROLLED = 'pre_enrolled';
    public const STATUS_INVITED = 'invited_for_test';
    public const STATUS_TEST_COMPLETED = 'test_completed';
    public const STATUS_ADMITTED = 'admitted';
    public const STATUS_NOT_ADMITTED = 'not_admitted';
    public const STATUS_FINALIZED = 'enrollment_finalized';

    public const STATUSES = [
        self::STATUS_PRE_ENROLLED,
        self::STATUS_INVITED,
        self::STATUS_TEST_COMPLETED,
        self::STATUS_ADMITTED,
        self::STATUS_NOT_ADMITTED,
        self::STATUS_FINALIZED,
    ];

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'temporary_id',
        'first_name',
        'last_name',
        'post_name',
        'gender',
        'dob',
        'place_of_birth',
        'parent_name',
        'parent_phone',
        'parent_email',
        'student_parent_id',
        'requested_grade_level_id',
        'requested_class_section_id',
        'requested_option',
        'status',
        'test_at',
        'test_location',
        'test_notes',
        'test_score',
        'test_result',
        'converted_student_id',
        'source',
        'notes',
        'created_by',
        'finalized_at',
    ];

    protected $casts = [
        'dob' => 'date',
        'test_at' => 'datetime',
        'finalized_at' => 'datetime',
        'test_score' => 'decimal:2',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function requestedGradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'requested_grade_level_id');
    }

    public function requestedClassSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class, 'requested_class_section_id');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }

    public function studentParent(): BelongsTo
    {
        return $this->belongsTo(StudentParent::class, 'student_parent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->post_name,
            $this->last_name,
        ])));
    }

    public function statusLabel(): string
    {
        $key = 'pre_enrollment.status_' . $this->status;
        $label = __($key);

        return $label === $key ? ucfirst(str_replace('_', ' ', $this->status)) : $label;
    }
}
