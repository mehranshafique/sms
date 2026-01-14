<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\StudentEnrollment;
use App\Models\ClassSection;
use App\Models\InstitutionSetting; 
use App\Enums\AcademicType; 
use App\Models\Subject; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; 

class ReportController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
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
            'trimester' => 'nullable|integer|in:1,2,3',
            'semester' => 'nullable|integer|in:1,2',
            'period' => 'nullable|string|in:p1,p2,p3,p4,p5,p6', // Added Period support
            'type' => 'nullable|in:period,term', // New: Report Type
        ]);

        $student = Student::with(['institution', 'enrollments.classSection.gradeLevel'])->findOrFail($request->student_id);
        
        // Security Check
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access.');
        }

        $enrollment = $student->enrollments()->latest()->first();
        if (!$enrollment) return back()->with('error', __('reports.no_enrollment'));

        // Cycle Logic
        $cycle = $enrollment->classSection->gradeLevel->education_cycle ?? AcademicType::PRIMARY; 
        
        if ($cycle instanceof AcademicType) {
            $cycleValue = $cycle->value;
        } else {
            $cycleValue = $cycle; 
        }

        // Fetch Settings
        $threshold = InstitutionSetting::get($institutionId, 'lmd_validation_threshold', 50);
        $gradingScale = json_decode(InstitutionSetting::get($institutionId, 'grading_scale', '[]'), true);

        // Branch Logic
        if ($cycleValue === 'university' || $cycleValue === 'lmd') {
            return $this->generateLmdTranscript($student, $enrollment, $request->semester, $threshold, $gradingScale);
        } elseif ($cycleValue === 'secondary') {
            // Check if Period Only or Full Semester
            if ($request->type === 'period' && $request->period) {
                return $this->generatePeriodBulletin($student, $enrollment, $request->period, $gradingScale);
            }
            return $this->generateSecondaryBulletin($student, $enrollment, $request->semester, $gradingScale);
        } else {
            // Primary Logic (can be expanded for periods too)
            if ($request->type === 'period' && $request->period) {
                return $this->generatePeriodBulletin($student, $enrollment, $request->period, $gradingScale);
            }
            return $this->generatePrimaryBulletin($student, $enrollment, $request->trimester, $gradingScale);
        }
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
        
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access.');
        }

        // Fetch Academic History
        $history = ExamRecord::with(['exam.academicSession', 'subject'])
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('exam.academic_session_id');

        $pdf = Pdf::loadView('reports.transcript', compact('student', 'history'));
        
        return $pdf->stream('Transcript_' . $student->admission_number . '.pdf');
    }

    // --- HELPER METHODS FOR BULLETINS ---

    private function generateLmdTranscript($student, $enrollment, $semester, $threshold, $gradingScale)
    {
        $query = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment) {
                $q->where('academic_session_id', $enrollment->academic_session_id);
            });

        if ($semester) {
            $query->whereHas('exam', function($q) use ($semester) {
                $q->where('category', 'like', "%session_$semester%");
            });
        }

        $records = $query->get();

        $pdf = Pdf::loadView('reports.transcript_lmd', compact('student', 'enrollment', 'records', 'semester', 'threshold', 'gradingScale'));
        return $pdf->stream('LMD_Transcript_'.$student->admission_number.'.pdf');
    }

    /**
     * Generates a simple bulletin for a single period (P1, P2, etc.)
     */
    private function generatePeriodBulletin($student, $enrollment, $period, $gradingScale)
    {
        // 1. Fetch Subjects
        $subjects = Subject::where('grade_level_id', $enrollment->classSection->grade_level_id)
            ->where('institution_id', $student->institution_id)
            ->orderBy('name')
            ->get();

        // 2. Fetch Marks for this specific period
        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $period) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->where('category', $period);
            })->get()->keyBy('subject_id');

        $data = [];
        foreach($subjects as $subject) {
            $rec = $records->get($subject->id);
            $data[] = [
                'subject' => $subject,
                'obtained' => $rec ? $rec->marks_obtained : '-',
                'max' => $subject->total_marks ?? 20,
                'percentage' => $rec ? ($rec->marks_obtained / ($subject->total_marks ?: 20)) * 100 : 0
            ];
        }

        $pdf = Pdf::loadView('reports.bulletin_period', compact('student', 'enrollment', 'period', 'data', 'gradingScale'));
        return $pdf->stream('Bulletin_Period_'.$period.'_'.$student->admission_number.'.pdf');
    }

    private function generatePrimaryBulletin($student, $enrollment, $trimester, $gradingScale)
    {
        $trimester = $trimester ?? 1;
        $pA = "p" . (($trimester * 2) - 1); 
        $pB = "p" . ($trimester * 2);       
        $examCat = "trimester_exam_$trimester";

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment) {
                $q->where('academic_session_id', $enrollment->academic_session_id);
            })->get();

        $data = [];
        foreach ($records as $r) {
            $subId = $r->subject_id;
            if (!isset($data[$subId])) {
                $data[$subId] = [
                    'subject' => $r->subject,
                    'p1_score' => 0, 'p2_score' => 0, 'exam_score' => 0,
                    'p_max' => $r->subject->total_marks ?? 20,
                    'exam_max' => ($r->subject->total_marks ?? 20) * 2 
                ];
            }
            
            if ($r->exam->category == $pA) $data[$subId]['p1_score'] = $r->marks_obtained;
            if ($r->exam->category == $pB) $data[$subId]['p2_score'] = $r->marks_obtained;
            if ($r->exam->category == $examCat) $data[$subId]['exam_score'] = $r->marks_obtained;
        }

        $pdf = Pdf::loadView('reports.bulletin_primary', compact('student', 'enrollment', 'data', 'trimester', 'gradingScale'));
        return $pdf->stream('Bulletin_Primary_'.$student->admission_number.'.pdf');
    }

    private function generateSecondaryBulletin($student, $enrollment, $semester, $gradingScale)
    {
        $semester = $semester ?? 1;
        
        $startPeriod = ($semester * 2) - 1; 
        $pA = "p" . $startPeriod;       
        $pB = "p" . ($startPeriod + 1); 
        $examCat = "semester_exam_$semester";

        $subjects = Subject::where('grade_level_id', $enrollment->classSection->grade_level_id)
            ->where('institution_id', $student->institution_id)
            ->orderBy('name')
            ->get();

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $pA, $pB, $examCat) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->whereIn('category', [$pA, $pB, $examCat]);
            })->get();

        $data = [];
        
        foreach($subjects as $subject) {
            $max = $subject->total_marks ?? 20; 
            $data[$subject->id] = [
                'subject' => $subject,
                'p1_score' => '-',
                'p2_score' => '-',
                'exam_score' => '-',
                'p_max' => $max,
                'exam_max' => $max * 2, 
                'total_score' => 0,
                'total_max' => ($max * 2) + ($max * 2), 
                'has_marks' => false
            ];
        }

        foreach ($records as $r) {
            $subId = $r->subject_id;
            if (isset($data[$subId])) {
                $cat = $r->exam->category;
                
                if ($cat == $pA) {
                    $data[$subId]['p1_score'] = $r->marks_obtained;
                    $data[$subId]['has_marks'] = true;
                }
                elseif ($cat == $pB) {
                    $data[$subId]['p2_score'] = $r->marks_obtained;
                    $data[$subId]['has_marks'] = true;
                }
                elseif ($cat == $examCat) {
                    $data[$subId]['exam_score'] = $r->marks_obtained;
                    $data[$subId]['has_marks'] = true;
                }
            }
        }

        foreach($data as $id => &$row) {
            $s1 = is_numeric($row['p1_score']) ? $row['p1_score'] : 0;
            $s2 = is_numeric($row['p2_score']) ? $row['p2_score'] : 0;
            $ex = is_numeric($row['exam_score']) ? $row['exam_score'] : 0;
            
            if($row['has_marks']) {
                $row['total_score'] = $s1 + $s2 + $ex;
            } else {
                $row['total_score'] = '-';
            }
        }

        $pdf = Pdf::loadView('reports.bulletin_secondary', compact('student', 'enrollment', 'semester', 'data', 'gradingScale'));
        return $pdf->stream('Bulletin_Secondary_'.$student->admission_number.'.pdf');
    }
}