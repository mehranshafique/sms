<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\StudentEnrollment;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ExamMarkController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':exam_mark.create')->only(['create', 'store']);
        $this->setPageTitle(__('marks.page_title'));
    }

    public function create(Request $request)
    {
        // FIX: Respect the header context switch
        $institutionId = session('active_institution_id') ?? Auth::user()->institute_id;

        // 1. Fetch Exams (Strictly 'ongoing' only)
        $examsQuery = Exam::where('status', 'ongoing');
        if ($institutionId) {
            $examsQuery->where('institution_id', $institutionId);
        }
        $exams = $examsQuery->pluck('name', 'id');

        // Initial load only requires Exams. 
        // Classes and Subjects will be loaded via AJAX for security and UX.
        $classes = []; 
        $subjects = [];
        $students = [];
        $existingMarks = [];

        return view('marks.create', compact('exams', 'classes', 'subjects', 'students', 'existingMarks'));
    }

    // --- AJAX HELPER METHODS ---

    public function getClasses(Request $request)
    {
        if(!$request->exam_id) return response()->json([]);
        $classes = $this->fetchClassesForExam($request->exam_id);
        
        // Rule 2: Section (Grade)
        $formattedClasses = $classes->mapWithKeys(function($item) {
             $grade = $item->gradeLevel->name ?? '';
             return [$item->id => $item->name . ($grade ? ' (' . $grade . ')' : '')];
        });

        return response()->json($formattedClasses);
    }

    public function getSubjects(Request $request)
    {
        if(!$request->class_section_id) return response()->json([]);
        $subjects = $this->fetchSubjectsForClass($request->class_section_id);
        
        // UPDATED: Return object structure with total_marks
        $formattedSubjects = $subjects->map(function($subject) {
            return [
                'id' => $subject->id,
                'name' => $subject->name,
                // Default to 100 if column doesn't exist or is null
                'total_marks' => $subject->total_marks ?? 100 
            ];
        });

        return response()->json($formattedSubjects);
    }

    /**
     * AJAX Method to fetch student list and existing marks.
     * Securely validates teacher access before returning data.
     */
    public function getStudents(Request $request)
    {
        if(!$request->exam_id || !$request->class_section_id || !$request->subject_id) {
            return response()->json(['message' => 'Missing required fields'], 400);
        }

        // Security Check
        if(!$this->validateAccess($request->exam_id, $request->class_section_id, $request->subject_id)) {
            return response()->json(['message' => __('marks.messages.unauthorized')], 403);
        }

        $exam = Exam::find($request->exam_id);
        if(!$exam) return response()->json(['message' => 'Exam not found'], 404);

        // Fetch Students
        $students = StudentEnrollment::with('student')
            ->where('class_section_id', $request->class_section_id)
            ->where('academic_session_id', $exam->academic_session_id)
            ->where('status', 'active')
            ->get()
            ->map(function($enrollment) {
                return [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->full_name,
                    // FIX: Use admission_number from student profile instead of table ID or roll_number
                    'admission_number' => $enrollment->student->admission_number ?? '-',
                ];
            })
            ->values();

        // Fetch Existing Marks
        $marks = ExamRecord::where('exam_id', $request->exam_id)
            ->where('subject_id', $request->subject_id)
            ->where('class_section_id', $request->class_section_id)
            ->get()
            ->keyBy('student_id')
            ->map(function($record) {
                return [
                    'marks_obtained' => $record->marks_obtained,
                    'is_absent' => $record->is_absent
                ];
            });

        return response()->json([
            'students' => $students,
            'marks' => $marks
        ]);
    }

    // --- PRIVATE HELPERS ---

    private function fetchClassesForExam($examId)
    {
        $exam = Exam::find($examId);
        if(!$exam) return collect();

        // FIX: Ensure we only show classes for the exam's institution
        $query = ClassSection::with('gradeLevel')
                             ->where('institution_id', $exam->institution_id)
                             ->where('is_active', true);

        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
             if ($user->staff) {
                 $staffId = $user->staff->id;
                 $query->where(function($q) use ($staffId) {
                     $q->where('staff_id', $staffId)
                       ->orWhereHas('timetables', function($t) use ($staffId) { 
                           $t->where('teacher_id', $staffId); 
                       }); 
                 });
             } else {
                 $query->whereRaw('1 = 0');
             }
        }
        
        return $query->get();
    }

    private function fetchSubjectsForClass($classId)
    {
        $class = ClassSection::find($classId);
        if(!$class) return collect();

        $query = Subject::where('grade_level_id', $class->grade_level_id)
                        ->where('institution_id', $class->institution_id) // FIX: Strict Institution Check
                        ->where('is_active', true);

        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                
                $validSubjectIds = Timetable::where('class_section_id', $classId)
                    ->where('teacher_id', $staffId) 
                    ->pluck('subject_id')
                    ->unique()
                    ->toArray();
                
                if (empty($validSubjectIds)) {
                     return collect();
                }
                    
                $query->whereIn('id', $validSubjectIds);
            } else {
                return collect();
            }
        }

        return $query->get();
    }

    private function validateAccess($examId, $classId, $subjectId)
    {
        $user = Auth::user();
        if ($user->hasRole(['Super Admin', 'Head Officer'])) return true;

        if ($user->hasRole('Teacher')) {
            if (!$user->staff) return false;

            $hasClasses = $this->fetchClassesForExam($examId)->pluck('id')->toArray();
            if(!in_array($classId, $hasClasses)) return false;

            $hasSubjects = $this->fetchSubjectsForClass($classId)->pluck('id')->toArray();
            if(!in_array($subjectId, $hasSubjects)) return false;
        }
        return true;
    }

    public function store(Request $request)
    {
        if(!$this->validateAccess($request->exam_id, $request->class_section_id, $request->subject_id)) {
            return response()->json(['message' => __('marks.messages.unauthorized')], 403);
        }

        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks' => 'required|array',
            'marks.*' => 'numeric|min:0',
            'absent' => 'nullable|array',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        
        if (($exam->finalized_at || $exam->status == 'published') && !Auth::user()->hasRole(['Super Admin', 'Head Officer'])) {
             return response()->json(['message' => __('exam.messages.exam_finalized_error')], 403);
        }

        $subject = Subject::findOrFail($request->subject_id);
        $maxMarks = $subject->total_marks ?? 100;

        DB::transaction(function () use ($request, $maxMarks) {
            foreach ($request->marks as $studentId => $mark) {
                
                $isAbsent = isset($request->absent[$studentId]);
                $finalMark = $isAbsent ? 0 : $mark;

                if ($finalMark > $maxMarks) {
                    throw new \Illuminate\Validation\ValidationException(\Illuminate\Support\Facades\Validator::make([], []), new \Illuminate\Http\JsonResponse(['message' => "Marks for student ID {$studentId} cannot exceed total marks ({$maxMarks})."], 422));
                }

                ExamRecord::updateOrCreate(
                    [
                        'exam_id' => $request->exam_id,
                        'student_id' => $studentId,
                        'subject_id' => $request->subject_id,
                    ],
                    [
                        'class_section_id' => $request->class_section_id, 
                        'marks_obtained' => $finalMark,
                        'is_absent' => $isAbsent,
                    ]
                );
            }
        });

        return response()->json([
            'message' =>(__('marks.messages.success_save')), 
            'redirect' => null 
        ]);
    }
}