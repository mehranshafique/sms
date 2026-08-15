<?php

namespace App\Services\Medical;

use App\Models\InfirmaryVisit;
use App\Models\Student;
use App\Models\StudentMedicalProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;

/**
 * Infirmary record access.
 *
 * The goal is not a hospital system: the nurse needs the few facts required to
 * help a student safely, and every read of that data is traceable.
 */
class StudentMedicalService
{
    public const AUDIT_MODULE = 'MedicalRecord';

    /**
     * Get (or lazily create) the medical profile for a student.
     */
    public function profileFor(Student $student): StudentMedicalProfile
    {
        $profile = $student->medicalProfile;

        if ($profile) {
            return $profile;
        }

        return $student->medicalProfile()->make([
            'institution_id' => $student->institution_id,
            'student_id' => $student->id,
            // Seed from the value already captured on the student profile.
            'blood_group' => $student->blood_group,
            'consent_first_aid' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveProfile(Student $student, array $data, User $actor): StudentMedicalProfile
    {
        $profile = $student->medicalProfile;
        $changedKeys = [];

        $payload = Arr::only($data, [
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
        ]);

        if ($profile) {
            $profile->fill($payload);
            $changedKeys = array_keys($profile->getDirty());
            $profile->updated_by = $actor->id;
            $profile->save();
            $action = 'Update';
        } else {
            $profile = StudentMedicalProfile::create(array_merge($payload, [
                'institution_id' => $student->institution_id,
                'student_id' => $student->id,
                'updated_by' => $actor->id,
            ]));
            $changedKeys = array_keys($payload);
            $action = 'Create';
        }

        // Keep the student profile's blood group in step with the infirmary record.
        if (array_key_exists('blood_group', $payload) && $payload['blood_group'] !== $student->blood_group) {
            $student->forceFill(['blood_group' => $payload['blood_group']])->save();
        }

        // Log which fields moved, never the health details themselves.
        AuditLogger::log(
            $action,
            self::AUDIT_MODULE,
            "{$action}d medical record for student #{$student->id}",
            null,
            ['student_id' => $student->id, 'fields' => array_values($changedKeys)]
        );

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordVisit(Student $student, array $data, User $actor): InfirmaryVisit
    {
        $visit = InfirmaryVisit::create(array_merge(
            Arr::only($data, [
                'visited_at',
                'reason',
                'observation',
                'action_taken',
                'temperature',
                'blood_pressure',
                'outcome',
                'parent_informed',
            ]),
            [
                'institution_id' => $student->institution_id,
                'student_id' => $student->id,
                'academic_session_id' => $data['academic_session_id'] ?? null,
                'recorded_by' => $actor->id,
                'parent_informed_at' => ! empty($data['parent_informed']) ? now() : null,
            ]
        ));

        AuditLogger::log(
            'Create',
            self::AUDIT_MODULE,
            "Recorded infirmary visit #{$visit->id} for student #{$student->id}",
            null,
            ['student_id' => $student->id, 'visit_id' => $visit->id, 'outcome' => $visit->outcome]
        );

        return $visit;
    }

    /**
     * Record that a medical record was opened. Reading health data is itself an
     * event worth keeping, so a school can answer "who looked at this?".
     */
    public function logAccess(Student $student, string $context = 'view'): void
    {
        AuditLogger::log(
            'View',
            self::AUDIT_MODULE,
            "Viewed medical record ({$context}) for student #{$student->id}",
            null,
            ['student_id' => $student->id, 'context' => $context]
        );
    }
}
