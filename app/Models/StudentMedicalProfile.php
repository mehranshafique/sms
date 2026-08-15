<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentMedicalProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Health fields that must never be written verbatim into the audit log.
     */
    public const SENSITIVE_FIELDS = [
        'allergies',
        'chronic_conditions',
        'current_medication',
        'medical_notes',
        'insurance_number',
    ];

    protected $fillable = [
        'institution_id',
        'student_id',
        'blood_group',
        'allergies',
        'chronic_conditions',
        'current_medication',
        'medical_notes',
        'doctor_name',
        'doctor_phone',
        'insurance_provider',
        'insurance_number',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'emergency_contact_alt_phone',
        'consent_first_aid',
        'information_date',
        'updated_by',
    ];

    protected $casts = [
        'consent_first_aid' => 'boolean',
        'information_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Does the profile carry anything the infirmary must react to?
     */
    public function hasCriticalInfo(): bool
    {
        return filled($this->allergies)
            || filled($this->chronic_conditions)
            || filled($this->current_medication);
    }

    /**
     * Emergency contact, falling back to the student's guardians.
     *
     * @return array{name: ?string, relation: ?string, phone: ?string}
     */
    public function emergencyContact(): array
    {
        if (filled($this->emergency_contact_phone)) {
            return [
                'name' => $this->emergency_contact_name,
                'relation' => $this->emergency_contact_relation,
                'phone' => $this->emergency_contact_phone,
            ];
        }

        $parent = $this->student?->parent;

        if (! $parent) {
            return ['name' => null, 'relation' => null, 'phone' => null];
        }

        $primary = $parent->primary_guardian ?? 'father';
        $map = [
            'father' => [$parent->father_name, __('medical.relation_father'), $parent->father_phone],
            'mother' => [$parent->mother_name, __('medical.relation_mother'), $parent->mother_phone],
            'guardian' => [$parent->guardian_name, $parent->guardian_relation, $parent->guardian_phone],
        ];

        foreach ([$primary, 'father', 'mother', 'guardian'] as $key) {
            [$name, $relation, $phone] = $map[$key] ?? [null, null, null];
            if (filled($phone)) {
                return ['name' => $name, 'relation' => $relation, 'phone' => $phone];
            }
        }

        return ['name' => null, 'relation' => null, 'phone' => null];
    }
}
