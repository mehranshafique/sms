<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends ChatbotBaseController
{
    /**
     * Submit Fee Extension (Derogation)
     * Legacy Option 7
     */
    public function submitDerogation(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'days' => 'required|integer|min:1|max:30',
        ]);

        $institutionId = $request->user()->institute_id;
        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        // Logic: Store in DB
        // Assuming a table 'fee_extensions' exists or creating a generic record
        // DB::table('fee_extensions')->insert([...]);
        
        $ticketId = "#DGR-" . rand(1000, 9999); // Mock ID
        
        return $this->sendResponse([
            'ticket_id' => $ticketId,
            'student' => $student->full_name,
            'duration' => $request->days . ' days'
        ], __('chatbot.derogation_submitted'));
    }

    /**
     * Submit Special Request (Absence, Late, etc.)
     * Legacy Option 8
     */
    public function submitRequest(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'type' => 'required|in:absence,late,dismissal,sick',
            'reason' => 'required|string'
        ]);

        $institutionId = $request->user()->institute_id;
        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        // Logic: Store in specific tables or generic requests table
        $ticketId = "#REQ-" . rand(1000, 9999);

        return $this->sendResponse([
            'ticket_id' => $ticketId,
            'type' => ucfirst($request->type),
            'status' => 'Pending'
        ], __('chatbot.request_submitted'));
    }
}