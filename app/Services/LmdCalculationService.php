<?php

namespace App\Services;

use App\Models\Student;
use App\Models\AcademicUnit;
use App\Models\ExamRecord;
use App\Models\InstitutionSetting;

class LmdCalculationService
{
    /**
     * Calculate LMD Results dynamically based on Program and Semester.
     */
    public function calculateSemesterResults(Student $student, $academicSessionId, $semester)
    {
        $enrollment = $student->enrollments()
            ->where('academic_session_id', $academicSessionId)
            ->where('status', 'active')
            ->first();

        if (!$enrollment || !$enrollment->gradeLevel->program_id) return null;

        $program = $enrollment->gradeLevel->program;

        // Fetch UEs belonging to this specific program and semester
        $units = AcademicUnit::with(['subjects'])
            ->where('program_id', $program->id)
            ->where('semester', $semester)
            ->get();

        if ($units->isEmpty()) return null;

        $records = ExamRecord::where('student_id', $student->id)
            ->whereHas('exam', fn($q) => $q->where('academic_session_id', $academicSessionId))
            ->get()
            ->keyBy('subject_id');

        $ueResults = [];
        $totalSemesterPoints = 0;
        $totalSemesterCoeffs = 0;
        $totalCreditsAttempted = 0;
        $totalCreditsEarned = 0;
        
        $validationThreshold = 10; 

        foreach ($units as $unit) {
            $uePoints = 0;
            $ueCoeffs = 0; 
            $ueCredits = 0;
            $subjectsData = [];

            foreach ($unit->subjects as $subject) {
                $record = $records->get($subject->id);
                $mark = $record ? $record->marks_obtained : 0;
                $maxMark = $subject->total_marks > 0 ? $subject->total_marks : 20;
                $normalizedMark = ($mark / $maxMark) * 20;
                
                $coeff = $subject->coefficient > 0 ? $subject->coefficient : 1;
                $credit = $subject->credit_hours;

                $uePoints += ($normalizedMark * $coeff);
                $ueCoeffs += $coeff;
                $ueCredits += $credit;

                $subjectsData[] = [
                    'name' => $subject->name,
                    'mark' => $mark,
                    'normalized' => $normalizedMark,
                    'credits' => $credit,
                    'coefficient' => $coeff
                ];
            }

            $ueAverage = $ueCoeffs > 0 ? ($uePoints / $ueCoeffs) : 0;
            $isValidated = $ueAverage >= $validationThreshold;
            $status = $isValidated ? 'V' : 'NV';
            $creditsEarned = $isValidated ? $ueCredits : 0;

            $ueResults[] = [
                'unit_name' => $unit->name,
                'unit_code' => $unit->code,
                'average' => number_format($ueAverage, 2),
                'total_credits' => $ueCredits,
                'credits_earned' => $creditsEarned,
                'status' => $status,
                'subjects' => $subjectsData
            ];

            $totalSemesterPoints += ($ueAverage * $ueCredits);
            $totalSemesterCoeffs += $ueCredits; 
            $totalCreditsAttempted += $ueCredits;
            $totalCreditsEarned += $creditsEarned;
        }

        $semesterAverage = $totalSemesterCoeffs > 0 ? ($totalSemesterPoints / $totalSemesterCoeffs) : 0;
        $isSemesterValidated = $semesterAverage >= $validationThreshold;
        
        if ($isSemesterValidated) {
            $totalCreditsEarned = $totalCreditsAttempted;
            foreach ($ueResults as &$ue) {
                if ($ue['status'] === 'NV') {
                    $ue['status'] = 'Cmp';
                    $ue['credits_earned'] = $ue['total_credits'];
                }
            }
        }

        return [
            'program' => $program->name,
            'semester' => $semester,
            'average' => number_format($semesterAverage, 2),
            'credits_attempted' => $totalCreditsAttempted,
            'credits_earned' => $totalCreditsEarned,
            'units' => $ueResults,
            'decision' => $isSemesterValidated ? __('lmd.admitted') : __('lmd.adjourned')
        ];
    }
}