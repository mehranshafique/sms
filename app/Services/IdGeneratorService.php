<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Institution;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Exception;

class IdGeneratorService
{
    /**
     * Generate a permanent Student ID.
     * Format: [InstitutionID][YY][XXXXX]
     * * InstitutionID = Unique ID of the school
     * YY = Last two digits of the academic year (end date)
     * XXXXX = 5-digit incremental unique number
     */
    public static function generateStudentId(Institution $institution, AcademicSession $session): string
    {
        return DB::transaction(function () use ($institution, $session) {
            // 1. Get Institution ID (e.g., 3405)
            $instId = $institution->id;

            // 2. Get YY from Academic Session End Date (e.g., 2025-2026 -> "26")
            $yearDigits = $session->end_date->format('y'); 

            // 3. Generate Incremental Number (XXXXX)
            $prefix = $instId . $yearDigits;
            $prefixLength = strlen($prefix);

            // Find the highest existing ID that starts with this prefix
            // Note: Using 'admission_number' to match the Student table schema
            $lastStudent = Student::where('admission_number', 'like', $prefix . '%')
                ->whereRaw("LENGTH(admission_number) = ?", [$prefixLength + 5]) // Ensure length matches exactly
                ->orderBy('admission_number', 'desc')
                ->lockForUpdate() // Prevent race conditions
                ->first();

            $nextSequence = 1;

            if ($lastStudent) {
                // Extract the sequence part (last 5 digits)
                $lastSequence = (int) substr($lastStudent->admission_number, $prefixLength);
                $nextSequence = $lastSequence + 1;
            }

            // Pad with zeros to 5 digits (e.g., 00457)
            $sequenceStr = str_pad($nextSequence, 5, '0', STR_PAD_LEFT);

            // Final ID: 34052600457
            return $prefix . $sequenceStr;
        });
    }
}