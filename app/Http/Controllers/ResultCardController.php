<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Models\Institution;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Barryvdh\DomPDF\Facade\Pdf;

class ResultCardController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('results.page_title'));
    }

    /**
     * Display the search filter or direct result for students.
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->hasRole('Student')) {
            return $this->studentIndex($user);
        }

        if (!$user->can('view result_card') && !$user->hasRole(['Super Admin', 'Head Officer', 'Teacher'])) {
            abort(403, __('results.unauthorized_access'));
        }

        $institutionId = session('active_institution_id') ?? $user->institute_id;
        $isGlobalView = ($institutionId === 'global' || ($user->hasRole('Super Admin') && !$institutionId));

        $query = Exam::query();

        if ($isGlobalView) {
            $query->withoutGlobalScopes();
        } else {
            $query->where('institution_id', $institutionId);
        }

        $exams = $query->latest()->get(); 

        $examsList = $exams->mapWithKeys(function ($exam) use ($isGlobalView) {
            $name = $exam->name;
            if ($isGlobalView && $exam->institution) {
                $name = $exam->institution->name . ' - ' . $name;
            } elseif ($isGlobalView && $exam->institution_id) {
                $name = __('results.inst_prefix') . " #{$exam->institution_id} - " . $name;
            }
            return [$exam->id => $name];
        });

        return view('results.index', ['exams' => $examsList]);
    }

    /**
     * View for a logged-in student
     */
    private function studentIndex($user)
    {
        if(!$user->student) {
            abort(403, __('results.student_profile_not_found'));
        }

        $studentId = $user->student->id;
        
        $examIds = ExamRecord::where('student_id', $studentId)
            ->pluck('exam_id')
            ->unique();
            
        $exams = Exam::whereIn('id', $examIds)
            ->where('status', 'published')
            ->latest()
            ->get();

        return view('results.student_index', compact('exams'));
    }

    /**
     * Generate the Result Card
     */
    public function print(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $user = Auth::user();
        
        $examQuery = Exam::with(['academicSession', 'institution']);
        if ($user->hasRole('Super Admin')) {
            $examQuery->withoutGlobalScopes();
        }
        $exam = $examQuery->findOrFail($request->exam_id);
        
        $activeInstId = session('active_institution_id') ?? $user->institute_id;
        $isGlobalSuperAdmin = $user->hasRole('Super Admin') && ($activeInstId === 'global' || !$activeInstId);
        
        if (!$isGlobalSuperAdmin && $exam->institution_id != $activeInstId && !$user->hasRole('Super Admin')) {
            abort(403, __('results.unauthorized_institution_context'));
        }

        if ($user->hasRole('Student')) {
            if ($user->student->id != $request->student_id) abort(403);
            if ($exam->status != 'published') abort(403, __('results.result_not_published'));
        } elseif ($user->hasRole('Teacher')) {
            $isAuthorized = $this->checkTeacherAccess($user, $request->student_id, $exam->id);
            if (!$isAuthorized) abort(403, __('results.teacher_unauthorized_view'));
        }

        // Fetch Records
        $records = ExamRecord::with('subject')
            ->where('exam_id', $exam->id)
            ->where('student_id', $request->student_id)
            ->get();

        if ($records->isEmpty()) {
             abort(404, __('results.no_marks_found_error'));
        }

        $enrollment = StudentEnrollment::with(['classSection.gradeLevel', 'student'])
            ->where('student_id', $request->student_id)
            ->where('academic_session_id', $exam->academic_session_id)
            ->first();

        $student = $enrollment ? $enrollment->student : $records->first()->student;
        $classSection = $records->first()->class_section ?? ($enrollment->classSection ?? null);

        $institutionType = $exam->institution->type ?? 'secondary';
        $data = $this->calculateResultData($records, $institutionType);

        $viewData = array_merge($data, [
            'exam' => $exam,
            'student' => $student,
            'enrollment' => $enrollment,
            'classSection' => $classSection,
            'institution' => $exam->institution,
            'type' => $institutionType
        ]);

        return view('results.print', $viewData);
    }

    // --- AJAX HELPER METHODS ---

    public function getClasses(Request $request)
    {
        if (!$request->exam_id) return response()->json([]);

        $exam = Exam::find($request->exam_id);
        if (!$exam) return response()->json([]);

        // FIX: Removed academic_session_id filter since your table structure (reused classes)
        // does not contain this column on class_sections or grade_levels.
        $classes = ClassSection::with('gradeLevel')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->id => $item->gradeLevel->name . ' - ' . $item->name];
            });

        return response()->json($classes);
    }

    public function getStudents(Request $request)
    {
        if (!$request->class_section_id) return response()->json([]);

        $query = StudentEnrollment::where('class_section_id', $request->class_section_id);

        // Optional: Filter by session if exam_id is provided to ensure we get current students only
        // This assumes StudentEnrollment has the academic_session_id column.
        if ($request->exam_id) {
            $exam = Exam::find($request->exam_id);
            if ($exam) {
                // We use where here safely assuming Enrollments track the session
                $query->where('academic_session_id', $exam->academic_session_id);
            }
        }

        $students = $query->with('student')
            ->get()
            ->filter(function($enrollment) {
                return $enrollment->student != null; // Remove orphans
            })
            ->map(function($enrollment) {
                $student = $enrollment->student;
                
                $name = $student->name 
                    ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
                    ?? ($student->user->name ?? __('results.unknown_student'));

                if (empty($name)) $name = __('results.student_placeholder', ['id' => $student->id]);

                return [
                    'id' => $student->id,
                    'name' => $name,
                    'roll_number' => $student->admission_number ?? __('results.na')
                ];
            })->values();
            
        return response()->json($students);
    }

    // --- PRIVATE METHODS ---

    private function calculateResultData($records, $type)
    {
        $totalMarks = 0;
        $obtainedMarks = 0;
        $totalCreditHours = 0;
        $totalGradePoints = 0;
        $subjectsData = [];

        foreach ($records as $record) {
            $max = $record->subject->total_marks ?? 100;
            $obt = $record->marks_obtained;
            $percentage = ($max > 0) ? ($obt / $max) * 100 : 0;
            $creditHours = $record->subject->credit_hours ?? 3;
            $grade = '';
            $gp = 0;

            if ($type === 'university') {
                if ($percentage >= 85) { $grade = 'A'; $gp = 4.0; }
                elseif ($percentage >= 80) { $grade = 'A-'; $gp = 3.7; }
                elseif ($percentage >= 75) { $grade = 'B+'; $gp = 3.3; }
                elseif ($percentage >= 70) { $grade = 'B'; $gp = 3.0; }
                elseif ($percentage >= 65) { $grade = 'B-'; $gp = 2.7; }
                elseif ($percentage >= 60) { $grade = 'C+'; $gp = 2.3; }
                elseif ($percentage >= 55) { $grade = 'C'; $gp = 2.0; }
                elseif ($percentage >= 50) { $grade = 'D'; $gp = 1.0; }
                else { $grade = 'F'; $gp = 0.0; }

                $totalCreditHours += $creditHours;
                $totalGradePoints += ($gp * $creditHours);
            } else {
                if ($percentage >= 90) $grade = 'A+';
                elseif ($percentage >= 80) $grade = 'A';
                elseif ($percentage >= 70) $grade = 'B';
                elseif ($percentage >= 60) $grade = 'C';
                elseif ($percentage >= 50) $grade = 'D';
                elseif ($percentage >= 40) $grade = 'E';
                else $grade = 'F';
            }

            $totalMarks += $max;
            $obtainedMarks += $obt;

            $subjectsData[] = [
                'name' => $record->subject->name,
                'code' => $record->subject->code ?? '-',
                'total' => $max,
                'obtained' => $obt,
                'percentage' => round($percentage, 1),
                'grade' => $grade,
                'gp' => $gp,
                'credit_hours' => $creditHours,
                'remarks' => $this->getRemarks($grade)
            ];
        }

        $gpa = ($totalCreditHours > 0) ? round($totalGradePoints / $totalCreditHours, 2) : 0;
        $overallPercentage = ($totalMarks > 0) ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0;
        $cgpa = $gpa; 

        return [
            'subjects' => $subjectsData,
            'summary' => [
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'percentage' => $overallPercentage,
                'gpa' => $gpa,
                'cgpa' => $cgpa,
                'total_credits' => $totalCreditHours
            ]
        ];
    }

    private function getRemarks($grade)
    {
        if (in_array($grade, ['A+', 'A', 'A-'])) return __('results.remarks_excellent');
        if (in_array($grade, ['B+', 'B', 'B-'])) return __('results.remarks_good');
        if (in_array($grade, ['C+', 'C'])) return __('results.remarks_satisfactory');
        if (in_array($grade, ['D', 'E'])) return __('results.remarks_pass');
        return __('results.remarks_fail');
    }

    private function checkTeacherAccess($user, $studentId, $examId)
    {
        if (!$user->staff) return false;
        
        $exam = Exam::find($examId);
        $enrollment = StudentEnrollment::where('student_id', $studentId)
            ->where('academic_session_id', $exam->academic_session_id)
            ->first();

        if (!$enrollment) return false;

        $classId = $enrollment->class_section_id;

        $isClassTeacher = ClassSection::where('id', $classId)->where('staff_id', $user->staff->id)->exists();
        if ($isClassTeacher) return true;
        
        return false;
    }
}