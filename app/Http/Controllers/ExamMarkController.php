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
use App\Models\ClassSubject; 
use App\Models\ExamSchedule; // Added
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

        $settingVal = InstitutionSetting::get($institutionId, 'active_periods', '[]');
        $activePeriods = json_decode($settingVal ?? '[]', true);

        $examsQuery = Exam::where('status', 'ongoing');
        
        if ($institutionId) {
            $examsQuery->where('institution_id', $institutionId);
        }

        $isTeacher = $user->hasRole(RoleEnum::TEACHER->value);
        $isAdmin = $user->hasRole([
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value, 
            RoleEnum::SCHOOL_ADMIN->value 
        ]);

        if ($isTeacher && !$isAdmin) {
            if (!empty($activePeriods)) {
                $examsQuery->whereIn('category', $activePeriods);
            } else {
                $examsQuery->whereRaw('1 = 0'); 
            }
        }

        $exams = $examsQuery->pluck('name', 'id');

        $classesQuery = ClassSection::with('gradeLevel')
            ->where('is_active', true);

        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }

        // Teacher visibility for Classes via Timetable AND Allocation
        if ($isTeacher && !$isAdmin && $user->staff) {
            $staffId = $user->staff->id;
            $classesQuery->where(function($q) use ($staffId) {
                $q->where('staff_id', $staffId) // Class Teacher
                  ->orWhereHas('timetables', function($t) use ($staffId) {
                      $t->where('teacher_id', $staffId); 
                  })
                  ->orWhereHas('classSubjects', function($c) use ($staffId) {
                      $c->where('teacher_id', $staffId);
                  });
            });
        }

        $classes = $classesQuery->get()->mapWithKeys(function ($item) {
            $name = ($item->gradeLevel->name ?? '') . ' ' . $item->name;
            return [$item->id => $name];
        });

        return view('marks.create', compact('exams', 'classes'));
    }

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
        $gradeLevelId = $request->grade_level_id;
        $classSectionId = $request->class_section_id;
        $examId = $request->exam_id;
        
        if (!$gradeLevelId && $classSectionId) {
            $section = ClassSection::find($classSectionId);
            if ($section) {
                $gradeLevelId = $section->grade_level_id;
            }
        }

        if (!$gradeLevelId) return response()->json([]);
        
        $user = Auth::user();
        $query = null;

        // Get Exam Session to filter Allocations correctly
        $examSessionId = null;
        if ($examId) {
            $exam = Exam::find($examId);
            if ($exam) $examSessionId = $exam->academic_session_id;
        }
        
        // Teacher Logic: Hybrid Check
        if ($user->hasRole(RoleEnum::TEACHER->value) && $user->staff) {
            $staffId = $user->staff->id;
            
            // 1. Timetable
            $timetableIds = Timetable::where('teacher_id', $staffId);
            if ($classSectionId) {
                $timetableIds->where('class_section_id', $classSectionId);
            } else {
                $timetableIds->whereHas('classSection', function($q) use ($gradeLevelId) {
                    $q->where('grade_level_id', $gradeLevelId);
                });
            }
            $ttIds = $timetableIds->pluck('subject_id')->toArray();

            // 2. Class Allocation
            $allocationIds = ClassSubject::where('teacher_id', $staffId);
            if ($classSectionId) {
                $allocationIds->where('class_section_id', $classSectionId);
            } else {
                $allocationIds->whereHas('classSection', function($q) use ($gradeLevelId) {
                    $q->where('grade_level_id', $gradeLevelId);
                });
            }
            // Filter by session if known
            if ($examSessionId) {
                $allocationIds->where('academic_session_id', $examSessionId);
            }

            $allocIds = $allocationIds->pluck('subject_id')->toArray();

            $subjectIds = array_unique(array_merge($ttIds, $allocIds));

            if (empty($subjectIds)) return response()->json([]);

            $query = Subject::whereIn('id', $subjectIds)->where('is_active', true);
        
        } else {
            // Admin Logic: Hybrid Fallback (Allocation -> Grade)
            if ($classSectionId) {
                $allocationQuery = ClassSubject::where('class_section_id', $classSectionId);
                
                if ($examSessionId) {
                    $allocationQuery->where('academic_session_id', $examSessionId);
                }

                $allocatedIds = $allocationQuery->pluck('subject_id');
                
                if ($allocatedIds->isNotEmpty()) {
                    // Respect Class Allocation (e.g. Latin vs Science)
                    $query = Subject::whereIn('id', $allocatedIds)->where('is_active', true);
                } else {
                    // Fallback to Grade Level
                    $query = Subject::where('grade_level_id', $gradeLevelId)->where('is_active', true);
                }
            } else {
                $query = Subject::where('grade_level_id', $gradeLevelId)->where('is_active', true);
            }
        }

        // Fetch Schedule Configs for this Exam+Class to override Max Marks
        $scheduleConfigs = collect();
        if ($examId && $classSectionId) {
            $scheduleConfigs = ExamSchedule::where('exam_id', $examId)
                ->where('class_section_id', $classSectionId)
                ->get()
                ->keyBy('subject_id');
        }

        $formattedSubjects = $query->get()->map(function($subject) use ($classSectionId, $scheduleConfigs) {
            $teacherName = $this->getSubjectTeacher($classSectionId, $subject->id);
            
            // Determine Max Marks: Schedule Config > Subject Default > 100
            $maxMarks = $subject->total_marks ?? 100;
            if (isset($scheduleConfigs[$subject->id])) {
                // FIX: Check if max_marks is specifically set (not null) and use it
                $configMark = $scheduleConfigs[$subject->id]->max_marks;
                if (!is_null($configMark) && $configMark > 0) {
                    $maxMarks = $configMark;
                }
            }

            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'total_marks' => (float)$maxMarks, // Ensure float for consistency
                'teacher_name' => $teacherName
            ];
        });

        return response()->json($formattedSubjects);
    }

    private function getSubjectTeacher($classId, $subjectId)
    {
        if(!$classId) return 'N/A';
        
        // 1. Try Allocation
        $alloc = ClassSubject::with('teacher.user')
            ->where('class_section_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
            
        if ($alloc && $alloc->teacher) {
            return $alloc->teacher->user->name;
        }

        // 2. Try Timetable
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
            if ($isSubjectTeacher) return true;

            $isAllocated = ClassSubject::where('class_section_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('teacher_id', $staffId)
                ->exists();
            
            return $isAllocated;
        }
        
        return false;
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

        // Determine Max Marks: Check Schedule First, then Subject
        $subject = Subject::findOrFail($request->subject_id);
        $maxMarks = $subject->total_marks ?? 100;

        $schedule = ExamSchedule::where('exam_id', $request->exam_id)
            ->where('class_section_id', $request->class_section_id)
            ->where('subject_id', $request->subject_id)
            ->first();

        // FIX: Prioritize schedule max_marks if available
        if ($schedule && !is_null($schedule->max_marks) && $schedule->max_marks > 0) {
            $maxMarks = $schedule->max_marks;
        }

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
        
        // Fetch Max Marks AND Pass Marks correctly for the award list
        $maxMarks = $subject->total_marks ?? 100;
        $passMarks = $subject->passing_marks ?? 40; // Default passing marks

        $schedule = ExamSchedule::where('exam_id', $exam->id)
            ->where('class_section_id', $classSection->id)
            ->where('subject_id', $subject->id)
            ->first();
            
        if ($schedule) {
            if (!is_null($schedule->max_marks) && $schedule->max_marks > 0) {
                $maxMarks = $schedule->max_marks;
            }
            if (!is_null($schedule->pass_marks) && $schedule->pass_marks > 0) {
                $passMarks = $schedule->pass_marks;
            }
        }
        
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
            'gradingScale', 'maxMarks', 'passMarks'
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
}