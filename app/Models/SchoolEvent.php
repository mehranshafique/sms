<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolEvent extends Model
{
    protected $fillable = [
        'institution_id',
        'name',
        'description',
        'event_date',
        'event_time',
        'venue',
        'contact',
        'audience',
        'class_section_ids',
        'status',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'class_section_ids' => 'array',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function invitations()
    {
        return $this->hasMany(SchoolEventInvitation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
