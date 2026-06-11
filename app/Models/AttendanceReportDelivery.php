<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceReportDelivery extends Model
{
    protected $fillable = [
        'institution_id',
        'student_id',
        'period_type',
        'period_start',
        'period_end',
        'channel',
        'sent_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'sent_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
