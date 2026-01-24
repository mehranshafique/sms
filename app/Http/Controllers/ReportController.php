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
use App\Models\Institution;
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
        $institution = Institution::find($institutionId);
        $institutionType = $institution->type ?? 'mixed'; 

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

        // Fetch Classes for Bulk Printing
        $classes = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->get();

        return view('reports.index', compact('exams', 'students', 'classes', 'institutionType'));
    }

    /**
     * Generate Student Bulletin (Term Report Card).
     * Supports Single Student OR Whole Class (Bulk)
     */
    public function bulletin(Request $request)
    {
        $request->validate([
            'student_id' => 'nullable|required_without:class_section_id|exists:students,id',
            'class_section_id' => 'nullable|required_without:student_id|exists:class_sections,id',
            'trimester' => 'nullable|integer|in:1,2,3',
            'semester' => 'nullable|integer|in:1,2',
            'period' => 'nullable|string|in:p1,p2,p3,p4,p5,p6', 
            'type' => 'nullable|in:period,term', 
        ]);

        $institutionId = $this->getInstitutionId();

        // 1. Validate Active Period
        if (!Auth::user()->hasRole('Super Admin')) {
            $activePeriodsJson = InstitutionSetting::get($institutionId, 'active_periods', '[]');
            $activePeriods = json_decode($activePeriodsJson, true) ?? [];
            
            $requestedPeriod = null;
            if ($request->type === 'period') $requestedPeriod = $request->period;
            elseif ($request->trimester) $requestedPeriod = 'trimester_' . $request->trimester;
            elseif ($request->semester) $requestedPeriod = 'semester_' . $request->semester;

            if (!empty($activePeriods) && $requestedPeriod && !in_array($requestedPeriod, $activePeriods)) {
                 $msg = __('reports.error_period_inactive', ['period' => strtoupper($requestedPeriod)]);
                 if ($request->ajax() || $request->check_only) return response()->json(['status' => 'error', 'message' => $msg]);
                 return back()->with('error', $msg);
            }
        }

        // 2. Identify Target Students
        $targetStudents = collect();
        $classSection = null;

        if ($request->class_section_id) {
            $classSection = ClassSection::with('gradeLevel')->find($request->class_section_id);
            if ($classSection->institution_id != $institutionId) abort(403);

            $enrollments = StudentEnrollment::with(['student', 'classSection.gradeLevel'])
                ->where('class_section_id', $request->class_section_id)
                ->where('status', 'active')
                ->get();
            
            if ($enrollments->isEmpty()) {
                $msg = __('reports.no_students_in_class');
                if ($request->ajax() || $request->check_only) return response()->json(['status' => 'error', 'message' => $msg]);
                return back()->with('error', $msg);
            }
            
            $targetStudents = $enrollments; 
        } else {
            $student = Student::with(['institution', 'enrollments.classSection.gradeLevel'])->findOrFail($request->student_id);
            if ($student->institution_id != $institutionId) abort(403);

            $enrollment = $student->enrollments()->latest()->first();
            if (!$enrollment) {
                $msg = __('reports.no_enrollment');
                if ($request->ajax() || $request->check_only) return response()->json(['status' => 'error', 'message' => $msg]);
                return back()->with('error', $msg);
            }
            
            $classSection = $enrollment->classSection;
            $targetStudents = collect([$enrollment]);
        }

        // 3. Prepare Data
        $bulkData = [];
        $settings = [
            'threshold' => InstitutionSetting::get($institutionId, 'lmd_validation_threshold', 50),
            'gradingScale' => json_decode(InstitutionSetting::get($institutionId, 'grading_scale', '[]'), true)
        ];

        foreach ($targetStudents as $enrollment) {
            $student = $enrollment->student;
            $cycle = $enrollment->classSection->gradeLevel->education_cycle ?? AcademicType::PRIMARY; 
            $cycleValue = ($cycle instanceof AcademicType) ? $cycle->value : $cycle;

            $reportData = null;
            $viewName = '';

            if ($cycleValue === 'university' || $cycleValue === 'lmd') {
                continue; 
            } 
            elseif ($cycleValue === 'secondary') {
                $viewName = 'reports.bulletin_secondary';
                if ($request->type === 'period') {
                    $viewName = 'reports.bulletin_period';
                    $reportData = $this->getPeriodData($student, $enrollment, $request->period);
                } else {
                    $reportData = $this->getSecondaryData($student, $enrollment, $request->semester);
                }
            } 
            else {
                // Primary
                $viewName = 'reports.bulletin_primary';
                if ($request->type === 'period') {
                    $viewName = 'reports.bulletin_period';
                    $reportData = $this->getPeriodData($student, $enrollment, $request->period);
                } else {
                    $reportData = $this->getPrimaryData($student, $enrollment, $request->trimester);
                }
            }

            if ($reportData && $this->hasMarks($reportData)) {
                $bulkData[] = array_merge($reportData, [
                    'student' => $student,
                    'enrollment' => $enrollment,
                    'settings' => $settings,
                    'request' => $request->all()
                ]);
            }
        }

        // --- AJAX CHECK RESPONSE ---
        if ($request->check_only) {
            if (empty($bulkData)) {
                return response()->json(['status' => 'error', 'message' => __('reports.no_records_found')]);
            }
            return response()->json(['status' => 'success']);
        }

        if (empty($bulkData)) {
            return back()->with('error', __('reports.no_records_found'));
        }

        // 4. Generate PDF
        if (count($bulkData) === 1) {
            $data = $bulkData[0];
            $pdf = Pdf::loadView($viewName, $data); 
            return $pdf->stream('Bulletin_'.$data['student']->admission_number.'.pdf');
        } else {
            $pdf = Pdf::loadView('reports.bulk_print', [
                'reports' => $bulkData, 
                'viewName' => $viewName,
                'classSection' => $classSection
            ]);
            return $pdf->stream('Class_Bulletin_'.$classSection->name.'.pdf');
        }
    }

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

        $history = ExamRecord::with(['exam.academicSession', 'subject'])
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('exam.academic_session_id');

        // --- AJAX CHECK RESPONSE ---
        if ($request->check_only) {
            if ($history->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => __('reports.no_records_found')]);
            }
            return response()->json(['status' => 'success']);
        }

        if ($history->isEmpty()) {
            return back()->with('error', __('reports.no_records_found'));
        }

        $pdf = Pdf::loadView('reports.transcript', compact('student', 'history'));
        return $pdf->stream('Transcript_' . $student->admission_number . '.pdf');
    }

    // --- HELPERS ---

    private function hasMarks($reportData) {
        if (!isset($reportData['data'])) return false;
        foreach($reportData['data'] as $row) {
            if (isset($row['has_marks']) && $row['has_marks']) return true; 
            if (isset($row['obtained']) && is_numeric($row['obtained'])) return true;
            if (isset($row['exam_score']) && is_numeric($row['exam_score'])) return true;
        }
        return false;
    }

    private function getPeriodData($student, $enrollment, $period)
    {
        $subjects = Subject::where('grade_level_id', $enrollment->classSection->grade_level_id)
            ->where('institution_id', $student->institution_id)
            ->orderBy('name')
            ->get();

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $period) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->where('category', $period);
            })->get()->keyBy('subject_id');

        $data = [];
        $hasData = false;

        foreach($subjects as $subject) {
            $rec = $records->get($subject->id);
            $obtained = $rec ? $rec->marks_obtained : '-';
            if ($rec) $hasData = true;

            $data[] = [
                'subject' => $subject,
                'obtained' => $obtained,
                'max' => $subject->total_marks ?? 20,
                'percentage' => $rec ? ($rec->marks_obtained / ($subject->total_marks ?: 20)) * 100 : 0
            ];
        }

        if (!$hasData) return null;

        return ['period' => $period, 'data' => $data];
    }

    private function getPrimaryData($student, $enrollment, $trimester)
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

        if ($records->isEmpty()) return null;

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

        return ['data' => $data, 'trimester' => $trimester];
    }

    private function getSecondaryData($student, $enrollment, $semester)
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

        if ($records->isEmpty()) return null;

        $data = [];
        $hasAnyMarks = false;
        
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
                if ($cat == $pA) { $data[$subId]['p1_score'] = $r->marks_obtained; $data[$subId]['has_marks'] = true; $hasAnyMarks = true; }
                elseif ($cat == $pB) { $data[$subId]['p2_score'] = $r->marks_obtained; $data[$subId]['has_marks'] = true; $hasAnyMarks = true; }
                elseif ($cat == $examCat) { $data[$subId]['exam_score'] = $r->marks_obtained; $data[$subId]['has_marks'] = true; $hasAnyMarks = true; }
            }
        }

        if (!$hasAnyMarks) return null;

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

        return ['data' => $data, 'semester' => $semester];
    }
}