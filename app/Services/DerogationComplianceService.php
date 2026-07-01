<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Models\StudentRequest;

class DerogationComplianceService
{
    public function blocksAttendance(Student $student): bool
    {
        if (!InstitutionSetting::get($student->institution_id, 'block_attendance_on_expired_derogation', false)) {
            return false;
        }

        return StudentRequest::where('student_id', $student->id)
            ->where('type', 'fee_extension')
            ->where('status', 'expired')
            ->exists();
    }

    public function blocksResults(Student $student): bool
    {
        if (!InstitutionSetting::get($student->institution_id, 'block_results_on_expired_derogation', false)) {
            return false;
        }

        return StudentRequest::where('student_id', $student->id)
            ->where('type', 'fee_extension')
            ->where('status', 'expired')
            ->exists();
    }
}
