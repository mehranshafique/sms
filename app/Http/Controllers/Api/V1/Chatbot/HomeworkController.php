<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Assignment;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeworkController extends ChatbotBaseController
{
    /**
     * Get the latest assignment for the student's active class.
     * Legacy Equivalent: Option 1 (Devoir du jour)
     */
    public function getLatestHomework(Request $request)
    {
        $request->validate([
            'student_id' => 'required', // ID or Admission Number
        ]);

        $institutionId = $request->user()->institute_id;
        
        // 1. Find Student
        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)
                  ->orWhere('admission_number', $request->student_id);
            })
            ->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        // 2. Find Active Enrollment (to get Class Section)
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->latest('created_at')
            ->first();

        if (!$enrollment) return $this->sendError(__('chatbot.not_enrolled'), 200);

        // 3. Fetch Latest Assignment
        $assignment = Assignment::with('subject')
            ->where('institution_id', $institutionId)
            ->where('class_section_id', $enrollment->class_section_id)
            ->where('deadline', '>=', now()->subDays(7)) // Only show recent relevant homework
            ->latest()
            ->first();

        if (!$assignment) {
            // Return 200 OK because the request was successful, just no data to show
            return $this->sendError(__('chatbot.no_homework_found'), 200);
        }

        // 4. Prepare Response
        $fileUrl = $assignment->file_path ? asset('storage/' . $assignment->file_path) : null;

        return $this->sendResponse([
            'id' => $assignment->id,
            'subject' => $assignment->subject->name ?? 'General',
            'title' => $assignment->title,
            'description' => strip_tags($assignment->description), // Clean HTML if any
            'deadline' => $assignment->deadline->format('d M Y'),
            'has_file' => !is_null($fileUrl),
            'file_url' => $fileUrl,
            'teacher' => $assignment->teacher->user->name ?? 'Class Teacher'
        ], __('chatbot.latest_homework_retrieved'));
    }
}