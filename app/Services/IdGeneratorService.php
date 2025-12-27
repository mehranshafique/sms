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
     * XXXXX = 5-digit incremental unique number (Based on User ID if available)
     */
    public static function generateStudentId(Institution $institution, AcademicSession $session, ?int $userId = null): string
    {
        return DB::transaction(function () use ($institution, $session, $userId) {
            // 1. Get Institution ID (e.g., 3405)
            $instId = $institution->id;

            // 2. Get YY from Academic Session End Date (e.g., 2025-2026 -> "26")
            $yearDigits = $session->end_date->format('y'); 

            // 3. Generate Sequence Part
            $prefix = $instId . $yearDigits;

            if ($userId) {
                // PRIMARY STRATEGY: Use User ID as the unique sequence.
                // This creates a deterministic, unique ID based on the user table auto-increment.
                // Format: [InstID][YY][00123] (User ID 123)
                return $prefix . str_pad((string)$userId, 5, '0', STR_PAD_LEFT);
            }

            // FALLBACK STRATEGY: For students without Users
            // Use a random high-range number (80000+) to avoid collision with User IDs (which are usually lower).
            // We check for existence to be safe.
            do {
                $rand = mt_rand(80000, 99999);
                $id = $prefix . $rand;
            } while (Student::where('admission_number', $id)->exists());

            return $id;
        });
    }

    /**
     * Generate Institution Code.
     * Format: 2 digits City + 2 digits Commune + 4 digits Sequence
     * Example: 10130001 (City: 10, Commune: 13, Seq: 0001)
     */
    public static function generateInstitutionCode(string $city, string $commune): string
    {
        return DB::transaction(function () use ($city, $commune) {
            
            // 1. Process City Code (2 Digits)
            $cityCode = is_numeric($city) 
                ? $city 
                : substr(preg_replace('/[^0-9]/', '', (string) crc32($city)), 0, 2);
            
            $cityCode = str_pad(substr($cityCode, 0, 2), 2, '0', STR_PAD_LEFT);

            // 2. Process Commune/Location Code (2 Digits)
            $communeCode = is_numeric($commune) 
                ? $commune 
                : substr(preg_replace('/[^0-9]/', '', (string) crc32($commune)), 0, 2);
                
            $communeCode = str_pad(substr($communeCode, 0, 2), 2, '0', STR_PAD_LEFT);

            // 3. Generate Sequential Order (4 Digits)
            $lastId = Institution::max('id') ?? 0;
            $nextId = $lastId + 1;
            
            $sequence = str_pad($nextId, 4, '0', STR_PAD_LEFT);

            return $cityCode . $communeCode . $sequence;
        });
    }
}