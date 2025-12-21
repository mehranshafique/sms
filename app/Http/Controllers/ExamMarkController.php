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
        $institutionId = Auth::user()->institute_id;

        // 1. Fetch Exams (Strictly 'ongoing' only)
        $examsQuery = Exam::where('status', 'ongoing');
        if ($institutionId) {
            $examsQuery->where('institution_id', $institutionId);
        }
        $exams = $examsQuery->pluck('name', 'id');

        // 2. Fetch Classes
        $classesQuery = ClassSection::query();
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        
        // Filter classes for teacher (if role is teacher)
        if (Auth::user()->hasRole('Teacher')) {
             if (Auth::user()->staff) {
                 $staffId = Auth::user()->staff->id;
                 $classesQuery->where(function($q) use ($staffId) {
                     $q->where('staff_id', $staffId)
                       ->orWhereHas('timetables', function($t) use ($staffId) {
                           $t->where('staff_id', $staffId);
                       });
                 });
             } else {
                 // Teacher role but no staff profile? Show nothing to be safe.
                 $classesQuery->whereRaw('1 = 0');
             }
        }
        
        $classes = $classesQuery->pluck('name', 'id');

        $subjects = [];
        $students = [];
        $existingMarks = [];

        // 3. Logic for Dependent Dropdowns (Subjects)
        if ($request->filled('class_section_id')) {
             $selectedClass = ClassSection::find($request->class_section_id);
             if($selectedClass) {
                 $subjectsQuery = Subject::where('grade_level_id', $selectedClass->grade_level_id);
                 
                 // FILTER: Teachers see ONLY their timetable subjects
                 if (Auth::user()->hasRole('Teacher')) {
                     if (Auth::user()->staff) {
                         $staffId = Auth::user()->staff->id;
                         
                         // Get subject IDs from timetable where this teacher is assigned to this class
                         $allowedSubjectIds = Timetable::where('class_section_id', $selectedClass->id)
                            ->where('staff_id', $staffId)
                            ->pluck('subject_id')
                            ->unique()
                            ->toArray();
                            
                         $subjectsQuery->whereIn('id', $allowedSubjectIds);
                         $subjects = $subjectsQuery->pluck('name', 'id');
                     } else {
                         // Teacher without profile sees nothing
                         $subjects = [];
                     }
                 } else {
                     // Admin/Head Officer sees all subjects for the grade
                     $subjects = $subjectsQuery->pluck('name', 'id');
                 }
             }
        }

        // 4. Fetch Students & Marks
        if ($request->filled('exam_id') && $request->filled('class_section_id') && $request->filled('subject_id')) {
            if($this->validateAccess($request->exam_id, $request->class_section_id, $request->subject_id)) {
                $exam = Exam::find($request->exam_id);
                if ($exam) {
                    $students = StudentEnrollment::with('student')
                        ->where('class_section_id', $request->class_section_id)
                        ->where('academic_session_id', $exam->academic_session_id)
                        ->where('status', 'active')
                        ->get();

                    $existingMarks = ExamRecord::where('exam_id', $request->exam_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('class_section_id', $request->class_section_id)
                        ->get()
                        ->keyBy('student_id');
                }
            } else {
                abort(403, __('marks.messages.unauthorized'));
            }
        }

        return view('marks.create', compact('exams', 'classes', 'subjects', 'students', 'existingMarks'));
    }

    // --- AJAX HELPER METHODS ---

    public function getClasses(Request $request)
    {
        if(!$request->exam_id) return response()->json([]);
        $classes = $this->fetchClassesForExam($request->exam_id);
        return response()->json($classes->pluck('name', 'id'));
    }

    public function getSubjects(Request $request)
    {
        if(!$request->class_section_id) return response()->json([]);
        $subjects = $this->fetchSubjectsForClass($request->class_section_id);
        return response()->json($subjects->pluck('name', 'id'));
    }

    // --- PRIVATE HELPERS ---

    private function fetchClassesForExam($examId)
    {
        $exam = Exam::find($examId);
        if(!$exam) return collect();

        $query = ClassSection::where('institution_id', $exam->institution_id)
                             ->where('is_active', true);

        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
             if ($user->staff) {
                 $staffId = $user->staff->id;
                 $query->where(function($q) use ($staffId) {
                     $q->where('staff_id', $staffId) // Class Teacher
                       ->orWhereHas('timetables', function($t) use ($staffId) { 
                           $t->where('staff_id', $staffId); 
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
                        ->where('is_active', true);

        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                
                // Filter subjects based on timetable assignment
                $validSubjectIds = Timetable::where('class_section_id', $classId)
                    ->where('staff_id', $staffId)
                    ->pluck('subject_id')
                    ->unique()
                    ->toArray();
                    
                $query->whereIn('id', $validSubjectIds);
            } else {
                // Return empty if no staff profile found
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

        DB::transaction(function () use ($request) {
            foreach ($request->marks as $studentId => $mark) {
                $isAbsent = isset($request->absent[$studentId]);
                $finalMark = $isAbsent ? 0 : $mark;

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
            'redirect' => route('marks.create', [
                'exam_id' => $request->exam_id, 
                'class_section_id' => $request->class_section_id, 
                'subject_id' => $request->subject_id
            ])
        ]);
    }
}