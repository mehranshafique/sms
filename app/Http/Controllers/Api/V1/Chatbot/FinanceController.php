<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use App\Services\CurrencyService;

class FinanceController extends ChatbotBaseController
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

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

        $currencyPayload = $this->currencyService->apiPayload($institutionId);

        $data = $fees->map(function ($fee) use ($currencyPayload) {
            return [
                'name' => $fee->name,
                'amount' => number_format($fee->amount, 2),
                'currency' => $currencyPayload['symbol'],
                'currency_settings' => $currencyPayload,
            ];
        });

        return $this->sendResponse([
            'items' => $data,
            'currency' => $currencyPayload,
        ], __('chatbot.fees_retrieved'));
    }
}