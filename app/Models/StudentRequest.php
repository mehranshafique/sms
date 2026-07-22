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
        'reason_key',
        'reason_params',
        'reason_locale',
        'admin_note',       // NEW: Admin's response/reason sent to parent
        'start_date',
        'end_date',
        'payment_deadline',
        'status',           // submitted, under_review, approved, partially_approved, rejected, honored, expired
        'ticket_number',
        'created_by',       // User ID of creator (Student or Admin)
        'approved_by',      // User ID of approver
        'approved_at',
        'file_path'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_deadline' => 'date',
        'approved_at' => 'datetime',
        'reason_params' => 'array',
    ];

        public const STATUSES = [
        'submitted',
        'pending',
        'under_review',
        'approved',
        'partially_approved',
        'rejected',
        'additional_info_required',
        'honored',
        'expired',
    ];

    /** Student/parent request types (staff leave uses staff_leaves module). */
    public const STUDENT_TYPES = [
        'absence',
        'late',
        'sick',
        'early_exit',
        'fee_extension',
        'other',
    ];

    public function typeLabel(): string
    {
        $type = $this->resolvedType();
        $key = 'requests.type_' . $type;
        $label = __($key);

        return $label === $key ? ucfirst(str_replace('_', ' ', $type)) : $label;
    }

    /** Map legacy mis-typed chatbot records to the correct label. */
    public function resolvedType(): string
    {
        if ($this->type === 'leave' && $this->student_id) {
            return 'fee_extension';
        }

        return $this->type;
    }

    public function localizedReason(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($this->reason_key) {
            $translated = __($this->reason_key, $this->reason_params ?? [], $locale);

            if ($translated !== $this->reason_key) {
                return $translated;
            }
        }

        return (string) ($this->reason ?? '');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
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