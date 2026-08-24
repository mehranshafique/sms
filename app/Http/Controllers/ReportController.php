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
use App\Models\ExamSchedule; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; 
use App\Services\LmdCalculationService; 
use App\Services\GradeMentionService;
use App\Services\AcademicCycleService;
use App\Services\ApplicationGradeService;
use App\Services\AssessmentPeriodService;
use App\Services\StudentConductService;
use App\Services\ReportAuthorityService;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    protected $lmdService;
    protected AcademicCycleService $cycleService;
    protected AssessmentPeriodService $periodService;
    
    public function __construct(
        LmdCalculationService $lmdService,
        AcademicCycleService $cycleService,
        AssessmentPeriodService $periodService
    )
    {
        $this->middleware('auth')->except(['bulletinSigned']);
        $this->middleware(function ($request, $next) {
            $this->denyStudentLikeRoles();
            $this->authorizeAdminOrPermission('academic_report.view');
            return $next($request);
        })->only(['index', 'bulletin', 'transcript', 'scopeOptions']);
        $this->setPageTitle(__('reports.page_title'));
        $this->lmdService = $lmdService;
        $this->cycleService = $cycleService;
        $this->periodService = $periodService;
    }

    public function bulletinSigned(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'trimester' => 'nullable|integer|in:1,2,3',
            'semester' => 'nullable|integer|in:1,2',
            'period' => 'nullable|string|in:p1,p2,p3,p4,p5,p6,trimester_exam_1,trimester_exam_2,trimester_exam_3,semester_exam_1,semester_exam_2',
            'stage_key' => 'nullable|string',
            'type' => 'nullable|in:period,term',
        ]);

        $student = Student::findOrFail($request->student_id);

        return $this->renderBulletin($request, (int) $student->institution_id, skipRoleChecks: true);
    }

    /**
     * Soft empty-result response for AJAX pre-checks (info dialog, not danger).
     */
    protected function emptyReportJson(string $message, array $extra = [])
    {
        return response()->json(array_merge([
            'status' => 'info',
            'feedback' => 'info',
            'message' => $message,
        ], $extra));
    }

    public function checkFinancialClearance($studentId, $institutionId, $abort = true, ?string $periodKey = null, bool $enforcePayment = true)
    {
        if (!$studentId || !$institutionId) return true;

        $student = Student::find($studentId);
        if (!$student) return true;

        $result = app(\App\Services\ReportCardAccessService::class)
            ->check($student, (int) $institutionId, $periodKey, $enforcePayment);

        if ($result['allowed']) {
            return $result;
        }

        if ($abort) {
            abort(403, $result['message_en'] ?: (__('reports.financial_restriction_msg') ?? 'Access denied due to unpaid fees.'));
        }

        return $result;
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        if (!$institutionId || $institutionId === 'global') {
            return redirect()->route('dashboard')
                ->with('error', __('reports.select_school_context'));
        }

        $institution = Institution::find($institutionId);
        $institutionType = $institution->type ?? 'mixed'; 

        $exams = Exam::where('institution_id', $institutionId)->latest()->get();

        $students = Student::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->with(['enrollments' => function ($q) use ($institutionId) {
                $sessionId = $this->periodService->currentSessionId((int) $institutionId);
                $q->where('status', 'active');
                if ($sessionId) {
                    $q->where('academic_session_id', $sessionId);
                }
                $q->latest('id');
            }, 'enrollments.classSection.gradeLevel'])
            ->select('id', 'first_name', 'last_name', 'admission_number')
            ->orderBy('first_name')
            ->get()
            ->map(function ($student) {
                $enrollment = $student->enrollments->first();
                $student->education_cycle = $enrollment
                    ? $this->cycleService->resolveCycle($enrollment)
                    : AcademicType::PRIMARY->value;

                return $student;
            });

        $classes = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->get();

        return view('reports.index', compact('exams', 'students', 'classes', 'institutionType'));
    }

    public function scopeOptions(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (!$institutionId || $institutionId === 'global') {
            return $this->emptyReportJson(__('reports.select_school_context'));
        }

        $cycle = AcademicType::PRIMARY->value;
        $sessionId = $this->periodService->currentSessionId((int) $institutionId);

        if ($request->filled('student_id')) {
            $student = Student::with(['enrollments.classSection.gradeLevel'])
                ->where('institution_id', $institutionId)
                ->findOrFail($request->student_id);
            $enrollment = $this->activeEnrollment($student, (int) $institutionId);
            if (!$enrollment) {
                return $this->emptyReportJson(__('reports.no_enrollment'));
            }
            $cycle = $this->cycleService->resolveCycle($enrollment);
            $sessionId = (int) $enrollment->academic_session_id;
        } elseif ($request->filled('class_section_id')) {
            $classSection = ClassSection::with('gradeLevel')
                ->where('institution_id', $institutionId)
                ->findOrFail($request->class_section_id);
            $cycle = $this->cycleService->resolveCycle($classSection);
        }

        if (! $sessionId) {
            return $this->emptyReportJson(__('settings.no_current_session'));
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->periodService->scopeOptions(
                (int) $institutionId,
                (int) $sessionId,
                $cycle
            ),
        ]);
    }

    public function bulletin(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        return $this->renderBulletin($request, (int) $institutionId, skipRoleChecks: false);
    }

    protected function renderBulletin(Request $request, int $institutionId, bool $skipRoleChecks = false)
    {
        $request->validate([
            'student_id' => 'nullable|required_without:class_section_id|exists:students,id',
            'class_section_id' => 'nullable|required_without:student_id|exists:class_sections,id',
            'trimester' => 'nullable|integer|in:1,2,3',
            'semester' => 'nullable|integer|in:1,2',
            'period' => 'nullable|string|in:p1,p2,p3,p4,p5,p6,trimester_exam_1,trimester_exam_2,trimester_exam_3,semester_exam_1,semester_exam_2',
            'stage_key' => 'nullable|string',
            'type' => 'nullable|in:period,term',
        ]);

        $targetStudents = collect();
        $classSection = null;
        $accessResult = null;

        if ($request->filled('student_id')) {
            $student = Student::with(['institution.cityRelation', 'parent', 'enrollments.classSection.gradeLevel'])->findOrFail($request->student_id);
            if ($student->institution_id != $institutionId) abort(403);

            $enrollment = $this->resolveEnrollmentForReport(
                $student,
                $institutionId,
                $request->filled('class_section_id') ? (int) $request->class_section_id : null,
                $request->input('period')
            );
            if (!$enrollment) {
                $msg = __('reports.no_enrollment');
                if ($request->ajax() || $request->check_only) {
                    return $this->emptyReportJson($msg);
                }
                return back()->with('info', $msg);
            }

            $classSection = $enrollment->classSection;
            $targetStudents = collect([$enrollment]);
        } elseif ($request->filled('class_section_id')) {
            $classSection = ClassSection::with('gradeLevel')->find($request->class_section_id);
            if ($classSection->institution_id != $institutionId) abort(403);

            $enrollmentsQuery = StudentEnrollment::with(['student.institution.cityRelation', 'student.parent', 'classSection.gradeLevel'])
                ->where('class_section_id', $request->class_section_id)
                ->where('status', 'active');

            $currentSessionId = $this->periodService->currentSessionId($institutionId);
            if ($currentSessionId) {
                $enrollmentsQuery->where('academic_session_id', $currentSessionId);
            }

            $enrollments = $enrollmentsQuery->get();

            // Fallback: section may still have active enrollments on a prior session with marks.
            if ($enrollments->isEmpty()) {
                $enrollments = StudentEnrollment::with(['student.institution.cityRelation', 'student.parent', 'classSection.gradeLevel'])
                    ->where('class_section_id', $request->class_section_id)
                    ->where('status', 'active')
                    ->get();
            }

            if ($enrollments->isEmpty()) {
                $msg = __('reports.no_students_in_class');
                if ($request->ajax() || $request->check_only) {
                    return $this->emptyReportJson($msg);
                }
                return back()->with('info', $msg);
            }

            $targetStudents = $enrollments;
        } else {
            $msg = __('reports.select_student');
            if ($request->ajax() || $request->check_only) {
                return $this->emptyReportJson($msg);
            }
            return back()->with('info', $msg);
        }

        $referenceEnrollment = $targetStudents->first();
        $cycleValue = $this->cycleService->resolveCycle($referenceEnrollment);
        $sessionId = (int) $referenceEnrollment->academic_session_id;

        if ($this->cycleService->isUniversityCycle($cycleValue)) {
            $msg = __('reports.error_university_use_transcript');
            if ($request->ajax() || $request->check_only) {
                return $this->emptyReportJson($msg, ['redirect_transcript' => true]);
            }
            return back()->with('info', $msg);
        }

        $stage = $this->periodService->resolveFromRequest($request, $cycleValue);
        if (! $stage) {
            $msg = __('reports.error_invalid_period_for_cycle');
            if ($request->ajax() || $request->check_only) {
                return $this->emptyReportJson($msg);
            }
            return back()->with('info', $msg);
        }

        $request->merge(array_filter([
            'type' => $stage['type'],
            'period' => $stage['period'],
            'trimester' => $stage['trimester'],
            'semester' => $stage['semester'],
            'stage_key' => $stage['key'],
        ], fn ($v) => $v !== null && $v !== ''));

        // Re-resolve single-student enrollment now that the stage/period is known,
        // so we use the same session/section that holds the marks (matches bulk).
        if ($request->filled('student_id') && $targetStudents->count() === 1) {
            $student = $targetStudents->first()->student;
            $categories = $this->stageExamCategories($stage, $cycleValue);
            $refined = $this->resolveEnrollmentForReport(
                $student,
                $institutionId,
                $request->filled('class_section_id') ? (int) $request->class_section_id : (int) $targetStudents->first()->class_section_id,
                $categories
            );
            if ($refined) {
                $targetStudents = collect([$refined]);
                $classSection = $refined->classSection;
                $sessionId = (int) $refined->academic_session_id;
            }
        }

        $validationError = $this->cycleService->validateReportRequest(
            $cycleValue,
            $request->type,
            $request->period,
            $request->trimester ? (int) $request->trimester : null,
            $request->semester ? (int) $request->semester : null
        );

        if ($validationError) {
            if ($request->ajax() || $request->check_only) {
                return $this->emptyReportJson($validationError);
            }
            return back()->with('info', $validationError);
        }

        $isStaff = ! $skipRoleChecks;
        $adminViewable = $this->periodService->isAdminViewable($institutionId, $sessionId, $stage['key']);
        $official = $this->periodService->isOfficial($institutionId, $sessionId, $stage['key']);
        $underRevision = $this->periodService->isReopened($institutionId, $sessionId, $stage['key']);
        $latest = $this->periodService->latestOfficialStage($institutionId, $sessionId, $cycleValue);

        if ($isStaff) {
            if (! $adminViewable) {
                return $this->stageUnavailableResponse($request, $stage, $latest);
            }
        } elseif (! $official) {
            return $this->stageUnavailableResponse($request, $stage, $latest, $underRevision);
        }

        if ($request->filled('student_id')) {
            $accessResult = $this->checkFinancialClearance(
                $request->student_id,
                $institutionId,
                $skipRoleChecks,
                $stage['key'],
                $skipRoleChecks
            );
            if ($skipRoleChecks && is_array($accessResult) && empty($accessResult['allowed'])) {
                $msg = $accessResult['message_en'] ?: __('reports.financial_restriction_msg');
                if ($request->ajax() || $request->check_only) {
                    return $this->emptyReportJson($msg);
                }
                abort(403, $msg);
            }
        }

        $bulkData = [];
        $sealImage = InstitutionSetting::get($institutionId, 'report_seal_image', '');
        $authorityService = app(ReportAuthorityService::class);
        $settings = [
            'threshold' => InstitutionSetting::get($institutionId, 'lmd_validation_threshold', 50),
            'gradingScale' => json_decode(InstitutionSetting::get($institutionId, 'grading_scale', '[]'), true),
            'seal_position' => InstitutionSetting::get($institutionId, 'report_seal_position', 'center') ?: 'center',
            'seal_image' => $sealImage ?: null,
        ];

        $rankings = $this->calculateRankings($classSection, $request, $institutionId, $stage);
        $applicationService = app(ApplicationGradeService::class);
        $conductService = app(StudentConductService::class);
        $viewName = 'reports.bulletin_period';
        $subjectCount = 0;

        foreach ($targetStudents as $enrollment) {
            $student = $enrollment->student;
            $studentCycle = $this->cycleService->resolveCycle($enrollment);

            if ($this->cycleService->isUniversityCycle($studentCycle)) {
                continue;
            }

            $authority = $authorityService->forCycle((int) $institutionId, $studentCycle);
            $termNumber = (int) ($stage['trimester'] ?: $stage['semester'] ?: 1);
            $isPeriodCard = $stage['type'] === 'period';

            if ($isPeriodCard) {
                $viewName = 'reports.bulletin_period';
                $reportData = $this->getPeriodData($student, $enrollment, $stage['period']);
            } elseif ($this->cycleService->usesSemesterModel($studentCycle)) {
                $viewName = 'reports.bulletin_secondary';
                $reportData = $this->getSecondaryData($student, $enrollment, $stage['semester']);
            } else {
                $viewName = 'reports.bulletin_primary';
                $reportData = $this->getPrimaryData($student, $enrollment, $stage['trimester']);
            }

            $studentRank = $rankings[$student->id] ?? null;
            $mode = $isPeriodCard ? 'period' : 'term';
            $reportData['column_labels'] = $this->cycleService->columnLabels(
                $studentCycle,
                $isPeriodCard ? 1 : $termNumber,
                $mode
            );
            $reportData['term_title'] = $this->periodService->stageTitle($stage['key'], $studentCycle);
            $reportData['education_cycle'] = $studentCycle;
            $reportData['stage_key'] = $stage['key'];
            $reportData['under_revision'] = $underRevision && $isStaff;
            $reportData['ranks'] = [
                'section_rank' => $studentRank['section_rank'] ?? '-',
                'section_total' => $studentRank['section_total'] ?? '-',
                'grade_rank' => $studentRank['grade_rank'] ?? '-',
                'grade_total' => $studentRank['grade_total'] ?? '-',
                'total_score' => $studentRank['total_score'] ?? 0,
                'place_eff' => $studentRank['place_eff'] ?? '-',
            ];

            $pct = (float) ($reportData['percentage_total'] ?? 0);
            $reportData['application'] = $applicationService->fromPercentage($pct, $institutionId, $studentCycle);

            if ($isPeriodCard) {
                $scopeType = 'period';
                $scopeKey = (string) $stage['period'];
            } elseif ($this->cycleService->usesSemesterModel($studentCycle)) {
                $scopeType = 'semester';
                $scopeKey = (string) $termNumber;
            } else {
                $scopeType = 'trimester';
                $scopeKey = (string) $termNumber;
            }

            $reportData['conduct'] = $conductService->valueOrDash(
                (int) $student->id,
                (int) $enrollment->academic_session_id,
                $scopeType,
                $scopeKey
            );

            if ($request->filled('student_id') && is_array($accessResult) && ! empty($accessResult['staff_banner'])) {
                $reportData['outstanding_banner'] = app()->getLocale() === 'fr'
                    ? ($accessResult['message_fr'] ?? '')
                    : ($accessResult['message_en'] ?? '');
            }

            $subjectCount = max($subjectCount, count($reportData['data'] ?? []));

            $bulkData[] = array_merge($reportData, [
                'student' => $student,
                'enrollment' => $enrollment,
                'settings' => $settings,
                'authority' => $authority,
                'request' => $request->all(),
                'cards_per_page' => $this->periodService->cardsPerPage($stage['key'], $subjectCount),
            ]);
        }

        $cardsPerPage = $this->periodService->cardsPerPage($stage['key'], $subjectCount);
        foreach ($bulkData as &$row) {
            $row['cards_per_page'] = $cardsPerPage;
        }
        unset($row);

        if ($request->check_only) {
            if (empty($bulkData)) {
                return $this->emptyReportJson(__('reports.no_records_found'));
            }
            return response()->json(['status' => 'success']);
        }

        if (empty($bulkData)) {
            return back()->with('info', __('reports.no_records_found'));
        }

        if (count($bulkData) === 1) {
            return view($viewName, $bulkData[0]);
        }

        return view('reports.bulk_print', [
            'reports' => $bulkData,
            'viewName' => $viewName,
            'classSection' => $classSection,
            'cards_per_page' => $cardsPerPage,
        ]);
    }

    protected function stageUnavailableResponse(Request $request, array $stage, ?array $latest, bool $underRevision = false)
    {
        $msg = $underRevision
            ? __('reports.stage_under_revision', [
                'requested' => $stage['label'],
                'closed_on' => $latest['label'] ?? $stage['label'],
            ])
            : $this->periodService->unavailableMessage($stage['label'], $latest);

        $extra = [
            'latest_official' => $latest,
            'stage_key' => $stage['key'],
        ];

        if ($request->ajax() || $request->check_only) {
            return $this->emptyReportJson($msg, $extra);
        }

        return back()->with('info', $msg);
    }

    public function transcript(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::with(['institution', 'gradeLevel', 'parent', 'enrollments.academicSession'])->findOrFail($request->student_id);
        $institutionId = $this->getInstitutionId();
        
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access.');
        }

        $this->checkFinancialClearance($student->id, $institutionId, false, null, false);

        $cycle = $student->gradeLevel->education_cycle ?? 'primary';
        $cycleValue = is_object($cycle) ? $cycle->value : $cycle;
        
        $isLmd = in_array($cycleValue, ['university', 'lmd', 'mixed']);

        if ($isLmd) {
            $history = [];
            foreach($student->enrollments as $enrol) {
                $sessionId = $enrol->academic_session_id;
                $sessionName = $enrol->academicSession->name;

                $sem1 = $this->lmdService->calculateSemesterResults($student, $sessionId, 1);
                if ($sem1) $history[$sessionName]['Semester 1'] = $sem1;

                $sem2 = $this->lmdService->calculateSemesterResults($student, $sessionId, 2);
                if ($sem2) $history[$sessionName]['Semester 2'] = $sem2;
            }

            if ($request->check_only) {
                if (empty($history)) {
                    return $this->emptyReportJson(__('reports.no_records_found'));
                }
                return response()->json(['status' => 'success']);
            }

            $pdf = Pdf::loadView($request->input('format') === 'esu' ? 'reports.transcript_lmd_esu' : 'reports.transcript_lmd', compact('student', 'history'));
            return $pdf->stream('LMD_Transcript_' . $student->admission_number . '.pdf');

        } else {
            $records = ExamRecord::with(['exam.academicSession', 'subject'])
                ->where('student_id', $student->id)
                ->get();

            if ($records->isEmpty()) {
                if ($request->check_only) {
                    return $this->emptyReportJson(__('reports.no_records_found'));
                }
                return back()->with('info', __('reports.no_records_found'));
            }

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

    private function activeEnrollment(Student $student, int $institutionId): ?StudentEnrollment
    {
        return $this->resolveEnrollmentForReport($student, $institutionId, null, null);
    }

    /**
     * Prefer the enrollment that matches the selected class and has marks for the requested stage.
     */
    private function resolveEnrollmentForReport(
        Student $student,
        int $institutionId,
        ?int $classSectionId = null,
        string|array|null $periodCategory = null
    ): ?StudentEnrollment {
        $currentSessionId = $this->periodService->currentSessionId($institutionId);
        $base = $student->enrollments()->with('classSection.gradeLevel')->where('status', 'active');

        $candidates = (clone $base)
            ->when($currentSessionId, fn ($q) => $q->where('academic_session_id', $currentSessionId))
            ->when($classSectionId, fn ($q) => $q->where('class_section_id', $classSectionId))
            ->orderByDesc('id')
            ->get();

        if ($candidates->isEmpty() && $classSectionId) {
            $candidates = (clone $base)
                ->when($currentSessionId, fn ($q) => $q->where('academic_session_id', $currentSessionId))
                ->orderByDesc('id')
                ->get();
        }

        if ($candidates->isEmpty()) {
            $candidates = (clone $base)->orderByDesc('id')->get();
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        $categories = array_values(array_filter((array) $periodCategory));
        if ($categories === []) {
            return $candidates->first();
        }

        // Prefer an enrollment that actually has marks for this stage (Single must match Bulk).
        $matchWithMarks = function ($pool, bool $requireClassSection) use ($student, $categories) {
            foreach ($pool as $enrollment) {
                $query = ExamRecord::where('student_id', $student->id)
                    ->whereHas('exam', function ($q) use ($enrollment, $categories) {
                        $q->where('academic_session_id', $enrollment->academic_session_id)
                            ->whereIn('category', $categories);
                    });
                if ($requireClassSection) {
                    $query->where('class_section_id', $enrollment->class_section_id);
                }
                if ($query->exists()) {
                    return $enrollment;
                }
            }

            return null;
        };

        $pools = [$candidates];

        // Expand beyond current session when marks live on a prior active enrollment.
        $sectionPool = (clone $base)
            ->when($classSectionId, fn ($q) => $q->where('class_section_id', $classSectionId))
            ->orderByDesc('id')
            ->get();
        if ($sectionPool->isNotEmpty()) {
            $pools[] = $sectionPool;
        }
        $pools[] = (clone $base)->orderByDesc('id')->get();

        foreach ($pools as $pool) {
            $hit = $matchWithMarks($pool, true) ?? $matchWithMarks($pool, false);
            if ($hit) {
                return $hit;
            }
        }

        return $candidates->first();
    }

    /**
     * @return list<string>
     */
    private function stageExamCategories(array $stage, string $cycle): array
    {
        if (($stage['type'] ?? '') === 'period' && ! empty($stage['period'])) {
            return [(string) $stage['period']];
        }

        if (! empty($stage['trimester'])) {
            $keys = $this->cycleService->periodKeysForTerm(AcademicType::PRIMARY->value, (int) $stage['trimester']);

            return array_values(array_filter([$keys['pA'] ?? null, $keys['pB'] ?? null, $keys['examCat'] ?? null]));
        }

        if (! empty($stage['semester'])) {
            $keys = $this->cycleService->periodKeysForTerm(AcademicType::SECONDARY->value, (int) $stage['semester']);

            return array_values(array_filter([$keys['pA'] ?? null, $keys['pB'] ?? null, $keys['examCat'] ?? null]));
        }

        if (! empty($stage['period'])) {
            return [(string) $stage['period']];
        }

        return [];
    }

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

        $schedules = ExamSchedule::with('exam')
            ->whereIn('exam_id', $exams->pluck('id'))
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

    private function calculateRankings($classSection, Request $request, $institutionId, array $stage = [])
    {
        $cycle = $this->cycleService->resolveCycle($classSection);
        $categories = $this->cycleService->categoriesForRequest(
            $cycle,
            $request->type,
            $request->period,
            $request->trimester ? (int) $request->trimester : null,
            $request->semester ? (int) $request->semester : null
        );

        if (empty($categories)) {
            return [];
        }

        $sessionId = StudentEnrollment::where('class_section_id', $classSection->id)
            ->where('status', 'active')
            ->value('academic_session_id');

        $gradeEnrollments = StudentEnrollment::with('student')
            ->where('grade_level_id', $classSection->grade_level_id)
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->get();

        $studentIds = $gradeEnrollments->pluck('student_id');
        $students = $gradeEnrollments->mapWithKeys(fn ($enr) => [$enr->student_id => $enr->student]);

        $marks = ExamRecord::with('exam')
            ->whereIn('student_id', $studentIds)
            ->whereHas('exam', function ($q) use ($categories, $sessionId) {
                $q->whereIn('category', $categories);
                if ($sessionId) {
                    $q->where('academic_session_id', $sessionId);
                }
            })
            ->get()
            ->groupBy('student_id');

        $scheduleCache = [];
        $studentPercents = [];

        foreach ($gradeEnrollments as $enr) {
            $sectionId = (int) $enr->class_section_id;
            if (! isset($scheduleCache[$sectionId])) {
                $scheduleCache[$sectionId] = $this->getExamScheduleMaxMarks(
                    $sectionId,
                    (int) $enr->academic_session_id,
                    $categories
                );
            }

            $scheduleMap = $scheduleCache[$sectionId];
            $studentMarks = $marks->get($enr->student_id, collect());
            $obtained = 0.0;
            $max = 0.0;

            foreach ($categories as $cat) {
                foreach (($scheduleMap[$cat] ?? []) as $subjectId => $subjectMax) {
                    $max += (float) $subjectMax;
                    $rec = $studentMarks->first(function ($row) use ($cat, $subjectId) {
                        return (int) $row->subject_id === (int) $subjectId
                            && ($row->exam->category ?? null) === $cat;
                    });
                    if ($rec && is_numeric($rec->marks_obtained)) {
                        $obtained += (float) $rec->marks_obtained;
                    }
                }
            }

            $studentPercents[$enr->student_id] = $max > 0 ? ($obtained / $max) * 100 : 0.0;
        }

        uksort($studentPercents, function ($idA, $idB) use ($studentPercents, $students) {
            $scoreA = $studentPercents[$idA];
            $scoreB = $studentPercents[$idB];
            if (abs($scoreA - $scoreB) > 0.001) {
                return $scoreB <=> $scoreA;
            }
            $nameA = isset($students[$idA]) ? strtolower(trim(($students[$idA]->first_name ?? '') . ' ' . ($students[$idA]->last_name ?? ''))) : '';
            $nameB = isset($students[$idB]) ? strtolower(trim(($students[$idB]->first_name ?? '') . ' ' . ($students[$idB]->last_name ?? ''))) : '';

            return strcmp($nameA, $nameB);
        });

        $gradeRanks = [];
        $rank = 1;
        $prevScore = -1;
        $displayRank = 1;
        foreach ($studentPercents as $sId => $score) {
            if (abs($score - $prevScore) > 0.001) {
                $rank = $displayRank;
            }
            $gradeRanks[$sId] = $rank;
            $prevScore = $score;
            $displayRank++;
        }

        $sectionScores = [];
        foreach ($gradeEnrollments as $enr) {
            $sectionScores[$enr->class_section_id][$enr->student_id] = $studentPercents[$enr->student_id] ?? 0;
        }

        $finalRanks = [];
        $totalGradeStudents = count($studentPercents);

        foreach ($sectionScores as $scores) {
            uksort($scores, function ($idA, $idB) use ($scores, $students) {
                $scoreA = $scores[$idA];
                $scoreB = $scores[$idB];
                if (abs($scoreA - $scoreB) > 0.001) {
                    return $scoreB <=> $scoreA;
                }
                $nameA = isset($students[$idA]) ? strtolower(trim(($students[$idA]->first_name ?? '') . ' ' . ($students[$idA]->last_name ?? ''))) : '';
                $nameB = isset($students[$idB]) ? strtolower(trim(($students[$idB]->first_name ?? '') . ' ' . ($students[$idB]->last_name ?? ''))) : '';

                return strcmp($nameA, $nameB);
            });

            $sRank = 1;
            $sPrevScore = -1;
            $sDisplayRank = 1;
            $totalInSection = count($scores);

            foreach ($scores as $sId => $score) {
                if (abs($score - $sPrevScore) > 0.001) {
                    $sRank = $sDisplayRank;
                }

                $finalRanks[$sId] = [
                    'total_score' => $score,
                    'grade_rank' => $gradeRanks[$sId] ?? '-',
                    'grade_total' => $totalGradeStudents,
                    'section_rank' => $sRank,
                    'section_total' => $totalInSection,
                    'place_eff' => $this->formatPlaceEff($sRank, $totalInSection),
                ];

                $sPrevScore = $score;
                $sDisplayRank++;
            }
        }

        return $finalRanks;
    }

    private function formatPlaceEff(int $rank, int $total): string
    {
        return $this->ordinalRank($rank) . ' / ' . $total;
    }

    private function ordinalRank(int $rank): string
    {
        if (app()->getLocale() === 'fr') {
            return $rank === 1 ? '1er' : $rank . 'e';
        }

        $mod100 = $rank % 100;
        if ($mod100 >= 11 && $mod100 <= 13) {
            return $rank . 'th';
        }

        return $rank . match ($rank % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    private function getPeriodData($student, $enrollment, $period)
    {
        $subjects = $this->getSubjectsForSection($enrollment->classSection);

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $period) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->where('category', $period);
            })->get();

        $missingIds = $records->pluck('subject_id')->diff($subjects->pluck('id'));
        if ($missingIds->isNotEmpty()) {
            $subjects = $subjects->concat(
                Subject::whereIn('id', $missingIds)->where('is_active', true)->get()
            )->unique('id')->values();
        }

        $records = $records->keyBy('subject_id');

        $scheduleMap = $this->getExamScheduleMaxMarks(
            $enrollment->class_section_id, 
            $enrollment->academic_session_id, 
            [$period]
        );

        $data = [];

        foreach($subjects as $subject) {
            $rec = $records->get($subject->id);
            $obtained = $rec ? $rec->marks_obtained : null; 

            // Prefer schedule max; fall back to subject total_marks so Single cards are not all 0.
            $configuredMax = $scheduleMap[$period][$subject->id]
                ?? ($subject->total_marks ?? 0);

            $data[] = [
                'subject' => $subject,
                'obtained' => $obtained,
                'max' => $configuredMax,
                'percentage' => ($configuredMax > 0 && is_numeric($obtained)) ? ($obtained / $configuredMax) * 100 : 0,
                'has_marks' => !is_null($obtained) 
            ];
        }

        $totalObtained = 0;
        $totalMax = 0;
        foreach ($data as $row) {
            if (is_numeric($row['obtained'])) {
                $totalObtained += (float) $row['obtained'];
            }
            $totalMax += (float) ($row['max'] ?? 0);
        }
        $percentageTotal = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;

        return [
            'period' => $period,
            'data' => $data,
            'percentage_total' => $percentageTotal,
            'mention' => app(GradeMentionService::class)->fromPercentage($percentageTotal),
        ];
    }

    private function getPrimaryData($student, $enrollment, $trimester)
    {
        $trimester = $trimester ?? 1;
        $keys = $this->cycleService->periodKeysForTerm(AcademicType::PRIMARY->value, (int) $trimester);
        $pA = $keys['pA'];
        $pB = $keys['pB'];
        $examCat = $keys['examCat'];

        $subjects = $this->getSubjectsForSection($enrollment->classSection);

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $pA, $pB, $examCat) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->whereIn('category', [$pA, $pB, $examCat]);
            })->get();

        $missingIds = $records->pluck('subject_id')->diff($subjects->pluck('id'));
        if ($missingIds->isNotEmpty()) {
            $subjects = $subjects->concat(
                Subject::whereIn('id', $missingIds)->where('is_active', true)->get()
            )->unique('id')->values();
        }

        $scheduleMap = $this->getExamScheduleMaxMarks(
            $enrollment->class_section_id, 
            $enrollment->academic_session_id, 
            [$pA, $pB, $examCat]
        );

        $data = [];
        foreach ($subjects as $subject) {
            $p1_max = $scheduleMap[$pA][$subject->id] ?? 0;
            $p2_max = $scheduleMap[$pB][$subject->id] ?? 0;
            $exam_max = $scheduleMap[$examCat][$subject->id] ?? 0;
            $total_max = $p1_max + $p2_max + $exam_max;

            $data[$subject->id] = [
                'subject' => $subject,
                'p1_score' => null, 
                'p2_score' => null, 
                'exam_score' => null,
                'p1_max' => $p1_max,
                'p2_max' => $p2_max,
                'exam_max' => $exam_max,
                'total_max' => $total_max, 
                'total_score' => 0,
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

        foreach($data as $id => &$row) {
            $s1 = is_numeric($row['p1_score']) ? (float)$row['p1_score'] : 0;
            $s2 = is_numeric($row['p2_score']) ? (float)$row['p2_score'] : 0;
            $ex = is_numeric($row['exam_score']) ? (float)$row['exam_score'] : 0;
            $row['total_score'] = $s1 + $s2 + $ex;
        }

        $sumTotObt = array_sum(array_column($data, 'total_score'));
        $sumTotMax = array_sum(array_column($data, 'total_max'));
        $percentageTotal = $sumTotMax > 0 ? ($sumTotObt / $sumTotMax) * 100 : 0;
        $mention = app(GradeMentionService::class)->fromPercentage($percentageTotal);

        return ['data' => $data, 'trimester' => $trimester, 'mention' => $mention, 'percentage_total' => $percentageTotal];
    }

    private function getSecondaryData($student, $enrollment, $semester)
    {
        $semester = $semester ?? 1;
        $keys = $this->cycleService->periodKeysForTerm(AcademicType::SECONDARY->value, (int) $semester);
        $pA = $keys['pA'];
        $pB = $keys['pB'];
        $examCat = $keys['examCat'];

        $subjects = $this->getSubjectsForSection($enrollment->classSection);

        $records = ExamRecord::with(['subject', 'exam'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment, $pA, $pB, $examCat) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->whereIn('category', [$pA, $pB, $examCat]);
            })->get();

        $missingIds = $records->pluck('subject_id')->diff($subjects->pluck('id'));
        if ($missingIds->isNotEmpty()) {
            $subjects = $subjects->concat(
                Subject::whereIn('id', $missingIds)->where('is_active', true)->get()
            )->unique('id')->values();
        }

        $scheduleMap = $this->getExamScheduleMaxMarks(
            $enrollment->class_section_id, 
            $enrollment->academic_session_id, 
            [$pA, $pB, $examCat]
        );

        $data = [];
        
        foreach($subjects as $subject) {
            $p1_max = $scheduleMap[$pA][$subject->id] ?? 0;
            $p2_max = $scheduleMap[$pB][$subject->id] ?? 0;
            $exam_max = $scheduleMap[$examCat][$subject->id] ?? 0;
            $total_max = $p1_max + $p2_max + $exam_max;

            $data[$subject->id] = [
                'subject' => $subject,
                'p1_score' => null,
                'p2_score' => null,
                'exam_score' => null,
                'p1_max' => $p1_max,
                'p2_max' => $p2_max,
                'exam_max' => $exam_max, 
                'total_max' => $total_max, 
                'total_score' => 0,
                'has_marks' => false
            ];
        }

        foreach ($records as $r) {
            $subId = $r->subject_id;
            if (isset($data[$subId])) {
                $data[$subId]['has_marks'] = true;
                $cat = $r->exam->category;
                if ($cat == $pA) { $data[$subId]['p1_score'] = $r->marks_obtained; }
                elseif ($cat == $pB) { $data[$subId]['p2_score'] = $r->marks_obtained; }
                elseif ($cat == $examCat) { $data[$subId]['exam_score'] = $r->marks_obtained; }
            }
        }

        foreach($data as $id => &$row) {
            $s1 = is_numeric($row['p1_score']) ? (float)$row['p1_score'] : 0;
            $s2 = is_numeric($row['p2_score']) ? (float)$row['p2_score'] : 0;
            $ex = is_numeric($row['exam_score']) ? (float)$row['exam_score'] : 0;
            $row['total_score'] = $s1 + $s2 + $ex;
        }

        $sumTotObt = array_sum(array_column($data, 'total_score'));
        $sumTotMax = array_sum(array_column($data, 'total_max'));
        $percentageTotal = $sumTotMax > 0 ? ($sumTotObt / $sumTotMax) * 100 : 0;
        $mention = app(GradeMentionService::class)->fromPercentage($percentageTotal);

        return ['data' => $data, 'semester' => $semester, 'mention' => $mention, 'percentage_total' => $percentageTotal];
    }
}