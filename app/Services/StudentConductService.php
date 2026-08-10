<?php

namespace App\Services;

use App\Models\DisciplinaryRecord;
use App\Models\Student;
use App\Models\StudentConductRecord;

class StudentConductService
{
    /**
     * Suggest a conduct grade for primary students from open discipline incidents.
     */
    public function suggestForPrimary(Student $student, int $academicSessionId): string
    {
        $count = DisciplinaryRecord::query()
            ->where('student_id', $student->id)
            ->where('academic_session_id', $academicSessionId)
            ->where('status', '!=', 'cancelled')
            ->count();

        return match (true) {
            $count === 0 => 'A',
            $count === 1 => 'B',
            $count <= 3 => 'AB',
            default => 'C',
        };
    }

    public function find(
        int $studentId,
        int $academicSessionId,
        string $scopeType,
        string $scopeKey
    ): ?StudentConductRecord {
        return StudentConductRecord::query()
            ->where('student_id', $studentId)
            ->where('academic_session_id', $academicSessionId)
            ->where('scope_type', $scopeType)
            ->where('scope_key', (string) $scopeKey)
            ->first();
    }

    public function valueOrDash(
        int $studentId,
        int $academicSessionId,
        string $scopeType,
        string $scopeKey
    ): string {
        $record = $this->find($studentId, $academicSessionId, $scopeType, $scopeKey);

        return ($record && $record->conduct !== '') ? $record->conduct : '-';
    }
}
