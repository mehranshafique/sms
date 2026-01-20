<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ClassSection;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\StudentEnrollment;
use App\Models\Timetable;
use App\Models\InstitutionSetting; 
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Barryvdh\DomPDF\Facade\Pdf;

class ExamMarkController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':exam_mark.create')->only(['create', 'store', 'printAwardList']);
        $this->setPageTitle(__('marks.page_title'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $institutionId = session('active_institution_id') ?? $user->institute_id;

        // 1. Get List of Authorized Periods from Settings
        $settingVal = InstitutionSetting::get($institutionId, 'active_periods', '[]');
        $activePeriods = json_decode($settingVal ?? '[]', true);

        // 2. Fetch Exams filtered by: 
        //    a) Ongoing Status 
        //    b) Must belong to an "Active Period" (if user is Teacher AND NOT Admin)
        
        $examsQuery = Exam::where('status', 'ongoing');
        
        if ($institutionId) {
            $examsQuery->where('institution_id', $institutionId);
        }

        // Determine Role Context
        $isTeacher = $user->hasRole(RoleEnum::TEACHER->value);
        $isAdmin = $user->hasRole([
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value, 
            RoleEnum::SCHOOL_ADMIN->value // Ensure School Admin is included
        ]);

        // RESTRICTION LOGIC:
        // Only restrict if user is a Teacher AND NOT an Admin.
        // This prevents Admins (who might also have a teacher role for testing) from being blocked.
        if ($isTeacher && !$isAdmin) {
            if (!empty($activePeriods)) {
                $examsQuery->whereIn('category', $activePeriods);
            } else {
                // If no periods are active, show nothing to teacher
                $examsQuery->whereRaw('1 = 0'); 
            }
        }

        $exams = $examsQuery->pluck('name', 'id');

        // 3. Fetch Classes ($classes) for the View
        $classesQuery = ClassSection::with('gradeLevel')
            ->where('is_active', true);

        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }

        // Filter for Teachers: Only show classes they teach or are assigned to
        if ($isTeacher && !$isAdmin && $user->staff) {
            $staffId = $user->staff->id;
            $classesQuery->where(function($q) use ($staffId) {
                $q->where('staff_id', $staffId) // Class Teacher
                  ->orWhereHas('timetables', function($t) use ($staffId) {
                      $t->where('teacher_id', $staffId); // Subject Teacher
                  });
            });
        }

        $classes = $classesQuery->get()->mapWithKeys(function ($item) {
            $name = ($item->gradeLevel->name ?? '') . ' ' . $item->name;
            return [$item->id => $name];
        });

        // Pass $classes to the view
        return view('marks.create', compact('exams', 'classes'));
    }

    // =========================================================================
    // AJAX HELPER METHODS
    // =========================================================================

    public function getGrades(Request $request)
    {
        if(!$request->exam_id) return response()->json([]);
        
        $exam = Exam::find($request->exam_id);
        if(!$exam) return response()->json([]);

        $grades = GradeLevel::where('institution_id', $exam->institution_id)
            ->orderBy('order_index')
            ->pluck('name', 'id');

        return response()->json($grades);
    }

    public function getSections(Request $request)
    {
        if(!$request->grade_level_id) return response()->json([]);
        
        $query = ClassSection::with('gradeLevel')
                             ->where('grade_level_id', $request->grade_level_id)
                             ->where('is_active', true);

        $user = Auth::user();
        // Check for Teacher role strictly for data filtering
        if ($user->hasRole(RoleEnum::TEACHER->value) && $user->staff) {
            $staffId = $user->staff->id;
            $query->where(function($q) use ($staffId) {
                $q->where('staff_id', $staffId) // Class Teacher
                  ->orWhereHas('timetables', function($t) use ($staffId) { 
                      $t->where('teacher_id', $staffId); // Subject Teacher
                  }); 
            });
        }
        
        // Return "Grade Name Section Name"
        $sections = $query->get()->mapWithKeys(function($item) {
            $name = ($item->gradeLevel->name ?? '') . ' ' . $item->name;
            return [$item->id => $name];
        });

        return response()->json($sections);
    }

    public function getSubjects(Request $request)
    {
        // 1. Resolve Grade Level ID
        $gradeLevelId = $request->grade_level_id;
        
        // If class_section_id is provided but grade_level_id isn't, fetch grade from section
        if (!$gradeLevelId && $request->class_section_id) {
            $section = ClassSection::find($request->class_section_id);
            if ($section) {
                $gradeLevelId = $section->grade_level_id;
            }
        }

        if (!$gradeLevelId) return response()->json([]);
        
        $user = Auth::user();
        
        // 2. Teacher Logic: Trust the Timetable
        if ($user->hasRole(RoleEnum::TEACHER->value) && $user->staff) {
            $staffId = $user->staff->id;
            
            $timetableQuery = Timetable::where('teacher_id', $staffId);

            // If a specific section is selected, filter by it. 
            // Otherwise filter by all sections in the Grade.
            if ($request->class_section_id) {
                $timetableQuery->where('class_section_id', $request->class_section_id);
            } else {
                $timetableQuery->whereHas('classSection', function($q) use ($gradeLevelId) {
                    $q->where('grade_level_id', $gradeLevelId);
                });
            }

            $subjectIds = $timetableQuery->pluck('subject_id')->unique()->toArray();

            if (empty($subjectIds)) return response()->json([]);

            // Fetch Subjects by ID
            $query = Subject::whereIn('id', $subjectIds)->where('is_active', true);
        
        } else {
            // 3. Admin Logic: Show all subjects defined for the Grade
            $query = Subject::where('grade_level_id', $gradeLevelId)
                            ->where('is_active', true);
        }

        $formattedSubjects = $query->get()->map(function($subject) use ($request) {
            $teacherName = $this->getSubjectTeacher($request->class_section_id, $subject->id);
            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'total_marks' => $subject->total_marks ?? 100, 
                'teacher_name' => $teacherName
            ];
        });

        return response()->json($formattedSubjects);
    }

    public function getStudents(Request $request)
    {
        if(!$request->exam_id || !$request->class_section_id || !$request->subject_id) {
            return response()->json(['message' => __('marks.messages.missing_fields')], 400);
        }

        if(!$this->validateAccess($request->exam_id, $request->class_section_id, $request->subject_id)) {
            return response()->json(['message' => __('marks.messages.unauthorized')], 403);
        }

        $exam = Exam::find($request->exam_id);
        if(!$exam) return response()->json(['message' => __('marks.messages.exam_not_found')], 404);

        $students = StudentEnrollment::with('student')
            ->where('class_section_id', $request->class_section_id)
            ->where('academic_session_id', $exam->academic_session_id)
            ->where('status', 'active')
            ->get()
            ->map(function($enrollment) {
                return [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->full_name,
                    'admission_number' => $enrollment->student->admission_number ?? '-',
                ];
            })
            ->values();

        $marks = ExamRecord::where('exam_id', $request->exam_id)
            ->where('subject_id', $request->subject_id)
            ->where('class_section_id', $request->class_section_id)
            ->get()
            ->keyBy('student_id')
            ->map(function($record) {
                return [
                    'marks_obtained' => $record->marks_obtained,
                    'is_absent' => (bool)$record->is_absent
                ];
            });

        return response()->json([
            'students' => $students,
            'marks' => $marks
        ]);
    }

    // =========================================================================
    // STORE MARKS
    // =========================================================================

    public function store(Request $request)
    {
        if(!$this->validateAccess($request->exam_id, $request->class_section_id, $request->subject_id)) {
            return response()->json(['message' => __('marks.messages.unauthorized')], 403);
        }

        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks' => 'nullable|array', 
            'absent' => 'nullable|array',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        
        $isAdmin = Auth::user()->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);
        if (($exam->finalized_at || $exam->status == 'published') && !$isAdmin) {
             return response()->json(['message' => __('exam.messages.exam_finalized_error')], 403);
        }

        $subject = Subject::findOrFail($request->subject_id);
        $maxMarks = $subject->total_marks ?? 100;

        DB::transaction(function () use ($request, $maxMarks) {
            $marksInput = $request->input('marks', []);
            $absentInput = $request->input('absent', []);
            
            $allStudentIds = array_unique(array_merge(array_keys($marksInput), array_keys($absentInput)));

            foreach ($allStudentIds as $studentId) {
                
                $isAbsent = isset($absentInput[$studentId]); 
                $val = $marksInput[$studentId] ?? 0;
                $finalMark = $isAbsent ? 0 : $val;

                if ($finalMark > $maxMarks) {
                    throw new \Illuminate\Validation\ValidationException(
                        \Illuminate\Support\Facades\Validator::make([], []), 
                        new \Illuminate\Http\JsonResponse([
                            'message' => __('marks.messages.exceeds_max', ['id' => $studentId, 'max' => $maxMarks])
                        ], 422)
                    );
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
            'message' => __('marks.messages.success_save'), 
            'redirect' => null 
        ]);
    }

    // =========================================================================
    // PRINT AWARD LIST
    // =========================================================================

    public function printAwardList(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        if(!$this->validateAccess($request->exam_id, $request->class_section_id, $request->subject_id)) {
            abort(403, __('marks.messages.unauthorized'));
        }

        $exam = Exam::with('academicSession', 'institution')->findOrFail($request->exam_id);
        $classSection = ClassSection::with('gradeLevel')->findOrFail($request->class_section_id);
        $subject = Subject::findOrFail($request->subject_id);
        
        $students = StudentEnrollment::with('student')
            ->where('class_section_id', $classSection->id)
            ->where('academic_session_id', $exam->academic_session_id)
            ->where('status', 'active')
            ->orderBy('roll_number')
            ->get();

        $marks = ExamRecord::where('exam_id', $exam->id)
            ->where('class_section_id', $classSection->id)
            ->where('subject_id', $subject->id)
            ->get()
            ->keyBy('student_id');

        $totalStudents = $students->count();
        $absentCount = $marks->where('is_absent', true)->count();
        $presentCount = $marks->where('is_absent', false)->count();
        
        $teacherName = $this->getSubjectTeacher($classSection->id, $subject->id);

        $gradingScale = json_decode(InstitutionSetting::get($exam->institution_id, 'grading_scale', '[]'), true);
        
        if (empty($gradingScale)) {
            $gradingScale = [
                ['grade' => 'A', 'min' => 90], ['grade' => 'B', 'min' => 70],
                ['grade' => 'C', 'min' => 50], ['grade' => 'F', 'min' => 0]
            ];
        }

        $pdf = Pdf::loadView('marks.print_award_list', compact(
            'exam', 'classSection', 'subject', 'students', 'marks', 
            'totalStudents', 'absentCount', 'presentCount', 'teacherName',
            'gradingScale' 
        ));

        return $pdf->stream('Award_List_'.$classSection->name.'_'.$subject->name.'.pdf');
    }

    public function myMarks()
    {
        $user = Auth::user();
        if (!$user->hasRole(RoleEnum::STUDENT->value)) {
            abort(403, __('marks.messages.unauthorized'));
        }

        $student = $user->student;
        if (!$student) abort(404);

        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
        if (!$enrollment) {
            return view('marks.my_marks', ['error' => __('marks.messages.not_enrolled')]);
        }

        $records = ExamRecord::with(['exam', 'subject'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function($q) use ($enrollment) {
                $q->where('academic_session_id', $enrollment->academic_session_id)
                  ->where('status', 'published'); 
            })
            ->get()
            ->groupBy('exam_id');

        return view('marks.my_marks', compact('records', 'student'));
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function getSubjectTeacher($classId, $subjectId)
    {
        if(!$classId) return 'N/A';
        $tt = Timetable::with('teacher.user')
            ->where('class_section_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
        return $tt->teacher->user->name ?? 'N/A';
    }

    private function validateAccess($examId, $classId, $subjectId)
    {
        $user = Auth::user();
        if ($user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
            return true;
        }

        if ($user->hasRole(RoleEnum::TEACHER->value)) {
            if (!$user->staff) return false;
            $staffId = $user->staff->id;

            $isClassTeacher = ClassSection::where('id', $classId)
                ->where('staff_id', $staffId)
                ->exists();
            if ($isClassTeacher) return true;

            $isSubjectTeacher = Timetable::where('class_section_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('teacher_id', $staffId)
                ->exists();

            return $isSubjectTeacher;
        }
        
        return false;
    }
}