<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Folder: app/Http/Controllers/Api/V1/Chatbot/
 * Purpose: Handles Student ID verification and Staff/Admin verification for the bot.
 */
class VerificationController extends ChatbotBaseController
{
    public function verifyStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string', 
        ]);

        if ($validator->fails()) return $this->sendError(__('chatbot.validation_error'), 422, $validator->errors());

        $institutionId = $request->user()->institute_id;

        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('admission_number', $request->identifier)
                  ->orWhere('id', $request->identifier);
            })
            ->first();

        if (!$student) {
            return $this->sendError(__('chatbot.student_not_found'), 404);
        }

        return $this->sendResponse([
            'id' => $student->id, // Returned for next steps
            'full_name' => $student->full_name,
            'admission_no' => $student->admission_number,
            'grade' => $student->gradeLevel->name ?? 'N/A',
            'section' => $student->classSection->name ?? 'N/A'
        ], __('chatbot.student_verified'));
    }

    public function verifyStaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shortcode' => 'required|string', 
        ]);

        if ($validator->fails()) return $this->sendError(__('chatbot.validation_error'), 422, $validator->errors());

        $institutionId = $request->user()->institute_id;

        $user = User::where('institute_id', $institutionId)
            ->where('shortcode', $request->shortcode)
            ->first();

        if (!$user) {
            return $this->sendError(__('chatbot.staff_not_found'), 404);
        }

        return $this->sendResponse([
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->getRoleNames()->first()
        ], __('chatbot.staff_verified'));
    }
}