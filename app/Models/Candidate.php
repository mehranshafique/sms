<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'election_position_id',
        'student_id',
        'external_name',
        'external_photo',
        'external_class_label',
        'contact_phone',
        'contact_email',
        'manifesto',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(ElectionPosition::class, 'election_position_id');
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function displayName(): string
    {
        if ($this->student) {
            return trim($this->student->first_name.' '.$this->student->last_name);
        }

        return (string) ($this->external_name ?: __('voting.unknown_candidate'));
    }

    public function classLabel(): string
    {
        if ($this->student?->classSection) {
            return class_section_label($this->student->classSection, 'grade_dash_section');
        }

        return (string) ($this->external_class_label ?: '—');
    }

    public function photoUrl(): string
    {
        if ($this->student?->student_photo) {
            return asset('storage/'.$this->student->student_photo);
        }
        if ($this->external_photo) {
            return asset('storage/'.$this->external_photo);
        }

        return asset('images/no-image.png');
    }
}
