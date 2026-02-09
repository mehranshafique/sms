<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;

class FinanceController extends ChatbotBaseController
{
    /**
     * Get Miscellaneous/Connexes Fees for the student.
     * Legacy Option 5
     */
    public function getMiscFees(Request $request)
    {
        $request->validate(['student_id' => 'required']);
        $institutionId = $request->user()->institute_id;
        
        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)
                  ->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->latest()->first();

        if (!$enrollment) return $this->sendError(__('chatbot.not_enrolled'), 200);

        // Fetch One-Time Fees (Connexes) applicable to this student's grade/class
        $fees = FeeStructure::where('institution_id', $institutionId)
            ->where('frequency', 'one_time') // Assuming 'one_time' = Connexes
            ->where('academic_session_id', $enrollment->academic_session_id)
            ->where(function($q) use ($enrollment) {
                $q->where('grade_level_id', $enrollment->grade_level_id)
                  ->orWhere('class_section_id', $enrollment->class_section_id)
                  ->orWhereNull('grade_level_id'); // Global fees
            })
            ->get();

        if ($fees->isEmpty()) {
            return $this->sendError(__('chatbot.no_fees_found'), 200);
        }

        $data = $fees->map(function($fee) {
            return [
                'name' => $fee->name,
                'amount' => number_format($fee->amount, 2),
                'currency' => config('app.currency_symbol', '$')
            ];
        });

        return $this->sendResponse($data, __('chatbot.fees_retrieved'));
    }
}