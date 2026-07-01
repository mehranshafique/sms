<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolEventInvitation extends Model
{
    protected $fillable = [
        'school_event_id',
        'student_id',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'recipient_telegram_chat_id',
        'delivery_status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(SchoolEvent::class, 'school_event_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
