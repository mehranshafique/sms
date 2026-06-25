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
        'admin_note',       // NEW: Admin's response/reason sent to parent
        'start_date',
        'end_date',
        'status',           // pending, approved, partially_approved, rejected
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