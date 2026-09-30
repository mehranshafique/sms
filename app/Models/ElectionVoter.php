<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionVoter extends Model
{
    protected $fillable = [
        'election_id',
        'student_id',
        'voter_code',
        'display_name',
        'external_class_label',
        'contact_phone',
        'source',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function displayName(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        if ($this->student) {
            return trim($this->student->first_name.' '.$this->student->last_name);
        }

        return $this->voter_code;
    }
}
