<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\StudentEnrollment;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // Use explicit Facade import to avoid alias issues

class ReportController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Add permission middleware here if needed, e.g.
        // $this->middleware('permission:reports.view'); 
        $this->setPageTitle(__('reports.page_title'));
    }

    /**
     * Show the main reports dashboard/index page.
     */
    public function index()
    {
        $institutionId = $this->getInstitutionId();

        // Fetch Exams
        $exams = Exam::where('institution_id', $institutionId)
            ->latest()
            ->get();

        // Fetch Students (Active)
        $students = Student::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'admission_number')
            ->orderBy('first_name')
            ->get();

        return view('reports.index', compact('exams', 'students'));
    }

    /**
     * Generate Student Bulletin (Term Report Card).
     */
    public function bulletin(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'exam_id' => 'required|exists:exams,id',
        ]);

        $student = Student::with('institution')->findOrFail($request->student_id);
        $exam = Exam::with('academicSession')->findOrFail($request->exam_id);

        // Security Check: Ensure context matches
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access to student record.');
        }

        // Fetch Exam Records
        $records = ExamRecord::with('subject')
            ->where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->get();

        if ($records->isEmpty()) {
            return back()->with('error', __('reports.no_records_found'));
        }

        // Calculate Totals & Grades
        $summary = $this->calculateExamSummary($records);

        // Fetch Attendance Summary for the Term (Optional but recommended)
        $attendance = [
            'present' => 0,
            'absent' => 0,
            'total' => 0
        ]; 
        
        // Load View for PDF
        $pdf = Pdf::loadView('reports.bulletin', compact('student', 'exam', 'records', 'summary', 'attendance'));
        
        return $pdf->stream('Bulletin_' . $student->admission_number . '.pdf');
    }

    /**
     * Generate Student Transcript (Cumulative History).
     */
    public function transcript(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::with('institution')->findOrFail($request->student_id);
        
        // Security Check
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access.');
        }

        // Fetch Academic History (All Sessions)
        // Group by Session -> Exam
        $history = ExamRecord::with(['exam.academicSession', 'subject'])
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('exam.academic_session_id');

        $pdf = Pdf::loadView('reports.transcript', compact('student', 'history'));
        
        return $pdf->stream('Transcript_' . $student->admission_number . '.pdf');
    }

    /**
     * Helper to calculate totals, percentage, grade.
     */
    private function calculateExamSummary($records)
    {
        $totalMarks = 0;
        $obtainedMarks = 0;
        $subjectsCount = $records->count();

        foreach ($records as $record) {
            $max = $record->subject->total_marks ?? 100;
            $totalMarks += $max;
            $obtainedMarks += $record->marks_obtained;
        }

        $percentage = ($totalMarks > 0) ? ($obtainedMarks / $totalMarks) * 100 : 0;
        
        // Simple Grading Logic (Can be moved to a service or GradeLevel model)
        $grade = 'F';
        if ($percentage >= 90) $grade = 'A+';
        elseif ($percentage >= 80) $grade = 'A';
        elseif ($percentage >= 70) $grade = 'B';
        elseif ($percentage >= 60) $grade = 'C';
        elseif ($percentage >= 50) $grade = 'D';
        elseif ($percentage >= 40) $grade = 'E';

        return [
            'total_marks' => $totalMarks,
            'obtained_marks' => $obtainedMarks,
            'percentage' => round($percentage, 2),
            'grade' => $grade,
            'subjects_count' => $subjectsCount
        ];
    }
}