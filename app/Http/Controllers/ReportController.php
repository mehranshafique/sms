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
use App\Models\ClassSubject; 
use App\Models\ExamSchedule; // Added for Schedules
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; 
use App\Services\LmdCalculationService; // NEW Injection

class ReportController extends BaseController
{
    protected $lmdService;
    public function __construct(LmdCalculationService $lmdService)
    {
        $this->middleware('auth');
        $this->setPageTitle(__('reports.page_title'));
        $this->lmdService = $lmdService;
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

        // --- RANKING CALCULATION ---
        $rankings = $this->calculateRankings($classSection, $request, $institutionId);

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

            // Only add if data exists (marks)
            if ($reportData && $this->hasMarks($reportData)) {
                
                // Attach Ranking Data
                $studentRank = $rankings[$student->id] ?? null;
                $reportData['ranks'] = [
                    'section_rank' => $studentRank['section_rank'] ?? '-',
                    'section_total' => $studentRank['section_total'] ?? '-',
                    'grade_rank' => $studentRank['grade_rank'] ?? '-',
                    'grade_total' => $studentRank['grade_total'] ?? '-',
                    'total_score' => $studentRank['total_score'] ?? 0, 
                ];

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
            // Use the new bulk_print view
            $pdf = Pdf::loadView('reports.bulk_print', [
                'reports' => $bulkData, 
                'viewName' => $viewName,
                'classSection' => $classSection
            ]);
            return $pdf->stream('Class_Bulletin_'.$classSection->name.'.pdf');
        }
    }
    /**
     * Generate Academic Transcript (Cumulative History)
     * Handles both Standard and LMD formats based on student's grade cycle.
     */
    public function transcript(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::with(['institution', 'gradeLevel', 'enrollments.academicSession'])->findOrFail($request->student_id);
        $institutionId = $this->getInstitutionId();
        
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access.');
        }

        // Determine Cycle (LMD vs Standard)
        // Check if education_cycle is an Enum object or string
        $cycle = $student->gradeLevel->education_cycle ?? 'primary';
        $cycleValue = is_object($cycle) ? $cycle->value : $cycle;
        
        $isLmd = in_array($cycleValue, ['university', 'lmd', 'mixed']);

        if ($isLmd) {
            // --- LMD LOGIC ---
            $history = [];
            
            // Loop through all enrollments to build semester history
            foreach($student->enrollments as $enrol) {
                $sessionId = $enrol->academic_session_id;
                $sessionName = $enrol->academicSession->name;

                // Calculate for Sem 1 & 2
                // Ideally we should detect which semesters apply to this grade, but 1 & 2 is standard
                $sem1 = $this->lmdService->calculateSemesterResults($student, $sessionId, 1);
                if ($sem1) $history[$sessionName]['Semester 1'] = $sem1;

                $sem2 = $this->lmdService->calculateSemesterResults($student, $sessionId, 2);
                if ($sem2) $history[$sessionName]['Semester 2'] = $sem2;
            }

            if ($request->check_only) {
                if (empty($history)) return response()->json(['status' => 'error', 'message' => __('reports.no_records_found')]);
                return response()->json(['status' => 'success']);
            }

            // Load LMD specific view
            // NOTE: Using 'reports.transcript_lmd' which corresponds to the file updated above
            $pdf = Pdf::loadView('reports.transcript_lmd', compact('student', 'history'));
            return $pdf->stream('LMD_Transcript_' . $student->admission_number . '.pdf');

        } else {
            // --- STANDARD LOGIC (Primary/Secondary) ---
            // (Same logic as previously provided for standard schools)
            $records = ExamRecord::with(['exam.academicSession', 'subject'])
                ->where('student_id', $student->id)
                ->get();

            if ($records->isEmpty()) {
                if ($request->check_only) return response()->json(['status' => 'error', 'message' => __('reports.no_records_found')]);
                return back()->with('error', __('reports.no_records_found'));
            }

            // Calculate Max Marks Logic... (omitted for brevity, same as existing)
            $schedules = ExamSchedule::whereIn('exam_id', $records->pluck('exam_id'))
                ->whereIn('subject_id', $records->pluck('subject_id'))
                ->whereIn('class_section_id', $records->pluck('class_section_id'))
                ->get();
            
            $scheduleMap = [];
            foreach($schedules as $sch) {
                $key = $sch->exam_id . '_' . $sch->subject_id . '_' . $sch->class_section_id;
                $scheduleMap[$key] = $sch->max_marks;
            }

            foreach($records as $record) {
                $key = $record->exam_id . '_' . $record->subject_id . '_' . $record->class_section_id;
                $configuredMax = $scheduleMap[$key] ?? null;
                $defaultMax = $record->subject->total_marks ?? 100;
                $record->calculated_max_marks = ($configuredMax > 0) ? $configuredMax : $defaultMax;
            }

            $history = $records->groupBy('exam.academic_session_id');

            if ($request->check_only) return response()->json(['status' => 'success']);

            $pdf = Pdf::loadView('reports.transcript', compact('student', 'history'));
            return $pdf->stream('Transcript_' . $student->admission_number . '.pdf');
        }
    }

    // --- HELPERS ---
    // (Rest of the helpers remain the same as previous file...)

    private function hasMarks($reportData) {
        if (!isset($reportData['data'])) return false;
        foreach($reportData['data'] as $row) {
            if (isset($row['has_marks']) && $row['has_marks']) return true; 
            if (isset($row['obtained']) && is_numeric($row['obtained'])) return true;
            if (isset($row['exam_score']) && is_numeric($row['exam_score'])) return true;
            
            if (isset($row['p1_score']) && is_numeric($row['p1_score'])) return true;
        }
        return false;
    }

    private function getSubjectsForSection($classSection)
    {
        $allocatedIds = ClassSubject::where('class_section_id', $classSection->id)
            ->pluck('subject_id');

        if ($allocatedIds->isNotEmpty()) {
            return Subject::whereIn('id', $allocatedIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return Subject::where('grade_level_id', $classSection->grade_level_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getExamScheduleMaxMarks($classSectionId, $academicSessionId, $examCategories)
    {
        $exams = Exam::where('academic_session_id', $academicSessionId)
            ->whereIn('category', $examCategories)
            ->get();

        if ($exams->isEmpty()) return [];

        $schedules = ExamSchedule::whereIn('exam_id', $exams->pluck('id'))
            ->where('class_section_id', $classSectionId)
            ->get();

        $map = [];
        foreach($schedules as $sched) {
            $cat = $sched->exam->category ?? null; 
            if(!$cat) {
                 $cat = $exams->where('id', $sched->exam_id)->first()->category ?? null;
            }
            if ($cat && $sched->max_marks > 0) {
                $map[$cat][$sched->subject_id] = (float)$sched->max_marks;
            }
        }
        return $map;
    }

    private function calculateRankings($classSection, Request $request, $institutionId)
    {
        $categories = [];
        if ($request->type === 'period') {
            $categories = [$request->period];
        } elseif ($request->trimester) {
            $tri = $request->trimester;
            $categories = ["p" . (($tri * 2) - 1), "p" . ($tri * 2), "trimester_exam_$tri"];
        } elseif ($request->semester) {
            $sem = $request->semester;
            $startPeriod = ($sem * 2) - 1;
            $categories = ["p" . $startPeriod, "p" . ($startPeriod + 1), "semester_exam_$sem"];
        }

        $gradeEnrollments = StudentEnrollment::where('grade_level_id', $classSection->grade_level_id)
            ->where('institution_id', $institutionId)
            ->where('status', 'active') 
            ->get();

        $studentIds = $gradeEnrollments->pluck('student_id');

        $marks = ExamRecord::whereIn('student_id', $studentIds)
            ->whereHas('exam', function($q) use ($categories) {
                $q->whereIn('category', $categories);
            })
            ->select('student_id', 'marks_obtained')
            ->get();

        $studentScores = [];
        foreach ($marks as $mark) {
            if (!isset($studentScores[$mark->student_id])) {
                $studentScores[$mark->student_id] = 0;
            }
            $studentScores[$mark->student_id] += $mark->marks_obtained;
        }

        arsort($studentScores); 

        $gradeRanks = [];
        $rank = 1;
        $prevScore = -1;
        $displayRank = 1;
        
        foreach ($studentScores as $sId => $score) {
            if ($score != $prevScore) {
                $rank = $displayRank;
            }
            $gradeRanks[$sId] = $rank;
            $prevScore = $score;
            $displayRank++;
        }
        
        $sectionScores = [];
        $sectionTotals = []; 

        foreach ($gradeEnrollments as $enr) {
            if (isset($studentScores[$enr->student_id])) {
                $sectionScores[$enr->class_section_id][$enr->student_id] = $studentScores[$enr->student_id];
            }
        }

        $finalRanks = [];
        $totalGradeStudents = count($studentScores);

        foreach ($sectionScores as $secId => $scores) {
            arsort($scores);
            $sRank = 1;
            $sPrevScore = -1;
            $sDisplayRank = 1;
            $totalInSection = count($scores);

            foreach ($scores as $sId => $score) {
                if ($score != $sPrevScore) {
                    $sRank = $sDisplayRank;
                }
                
                $finalRanks[$sId] = [
                    'total_score' => $score,
                    'grade_rank' => $gradeRanks[$sId] ?? '-',
                    'grade_total' => $totalGradeStudents,
                    'section_rank' => $sRank,
                    'section_total' => $totalInSection
                ];

                $sPrevScore = $score;
                $sDisplayRank++;
            }
        }

        return $finalRanks;
    }

    private function getPeriodData($student, $enrollment, $period)
    {
        $subjects = $this->getSubjectsForSection($enrollment->classSection);

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $period) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->where('category', $period);
            })->get()->keyBy('subject_id');

        $scheduleMap = $this->getExamScheduleMaxMarks(
            $enrollment->class_section_id, 
            $enrollment->academic_session_id, 
            [$period]
        );

        $data = [];
        $hasData = false;

        foreach($subjects as $subject) {
            $rec = $records->get($subject->id);
            $obtained = $rec ? $rec->marks_obtained : '-';
            if ($rec) $hasData = true;

            $defaultMax = $subject->total_marks ?? 20;
            $configuredMax = $scheduleMap[$period][$subject->id] ?? $defaultMax;

            $data[] = [
                'subject' => $subject,
                'obtained' => $obtained,
                'max' => $configuredMax,
                'percentage' => $rec ? ($rec->marks_obtained / $configuredMax) * 100 : 0,
                'has_marks' => ($rec !== null)
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

        $subjects = $this->getSubjectsForSection($enrollment->classSection);

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $pA, $pB, $examCat) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->whereIn('category', [$pA, $pB, $examCat]);
            })->get();

        if ($records->isEmpty()) return null;

        $scheduleMap = $this->getExamScheduleMaxMarks(
            $enrollment->class_section_id, 
            $enrollment->academic_session_id, 
            [$pA, $pB, $examCat]
        );

        $data = [];
        foreach ($subjects as $subject) {
            $defaultMax = $subject->total_marks ?? 20;
            
            $p1_max = $scheduleMap[$pA][$subject->id] ?? $defaultMax;
            $p2_max = $scheduleMap[$pB][$subject->id] ?? $defaultMax;
            $exam_max = $scheduleMap[$examCat][$subject->id] ?? ($defaultMax * 2);

            $display_p_max = max($p1_max, $p2_max);

            $data[$subject->id] = [
                'subject' => $subject,
                'p1_score' => '-', 
                'p2_score' => '-', 
                'exam_score' => '-',
                'p_max' => $display_p_max,
                'exam_max' => $exam_max,
                'has_marks' => false
            ];
        }

        foreach ($records as $r) {
            $subId = $r->subject_id;
            if (isset($data[$subId])) {
                $data[$subId]['has_marks'] = true;
                if ($r->exam->category == $pA) $data[$subId]['p1_score'] = $r->marks_obtained;
                elseif ($r->exam->category == $pB) $data[$subId]['p2_score'] = $r->marks_obtained;
                elseif ($r->exam->category == $examCat) $data[$subId]['exam_score'] = $r->marks_obtained;
            }
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

        $subjects = $this->getSubjectsForSection($enrollment->classSection);

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $pA, $pB, $examCat) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->whereIn('category', [$pA, $pB, $examCat]);
            })->get();

        if ($records->isEmpty()) return null;

        $scheduleMap = $this->getExamScheduleMaxMarks(
            $enrollment->class_section_id, 
            $enrollment->academic_session_id, 
            [$pA, $pB, $examCat]
        );

        $data = [];
        $hasAnyMarks = false;
        
        foreach($subjects as $subject) {
            $defaultMax = $subject->total_marks ?? 20;

            $p1_max = $scheduleMap[$pA][$subject->id] ?? $defaultMax;
            $p2_max = $scheduleMap[$pB][$subject->id] ?? $defaultMax;
            $exam_max = $scheduleMap[$examCat][$subject->id] ?? ($defaultMax * 2);

            $display_p_max = max($p1_max, $p2_max);
            $total_max = $p1_max + $p2_max + $exam_max;

            $data[$subject->id] = [
                'subject' => $subject,
                'p1_score' => '-',
                'p2_score' => '-',
                'exam_score' => '-',
                'p_max' => $display_p_max,
                'exam_max' => $exam_max, 
                'total_score' => 0,
                'total_max' => $total_max, 
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