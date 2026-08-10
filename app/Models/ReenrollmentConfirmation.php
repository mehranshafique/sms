<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReenrollmentConfirmation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIAL = 'partial_confirmation';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';

    /** Statuses shown in the admin review queue. */
    public const QUEUE_STATUSES = [
        self::STATUS_PARTIAL,
        self::STATUS_PENDING_REVIEW,
    ];

    protected $fillable = [
        'institution_id',
        'campaign_id',
        'student_id',
        'from_enrollment_id',
        'from_class_section_id',
        'proposed_class_section_id',
        'approved_class_section_id',
        'status',
        'parent_confirmation_channel',
        'parent_confirmed_at',
        'parent_confirmed_by',
        'parent_note',
        'amount_required',
        'amount_paid',
        'payment_status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'target_enrollment_id',
        'invitation_sent_at',
        'last_reminder_at',
        'reminder_count',
    ];

    protected $casts = [
        'parent_confirmed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'invitation_sent_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'amount_required' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ReenrollmentCampaign::class, 'campaign_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'from_enrollment_id');
    }

    public function fromClassSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class, 'from_class_section_id');
    }

    public function proposedClassSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class, 'proposed_class_section_id');
    }

    public function approvedClassSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class, 'approved_class_section_id');
    }

    public function targetEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'target_enrollment_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function parentConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_confirmed_by');
    }

    public function isInReviewQueue(): bool
    {
        return in_array($this->status, self::QUEUE_STATUSES, true);
    }

    public function statusLabel(): string
    {
        $key = 'reenrollment.status_' . $this->status;
        $label = __($key);

        return $label === $key ? ucfirst(str_replace('_', ' ', $this->status)) : $label;
    }
}
