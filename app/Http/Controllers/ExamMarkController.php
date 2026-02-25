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
use App\Models\ExamSchedule; 
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

        $isAdmin = $user->hasRole([
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value, 
            RoleEnum::SCHOOL_ADMIN->value 
        ]);

        // Restrict Exams for Non-Admins based on active grading periods
        if (!$isAdmin) {
            if (!empty($activePeriods)) {
                $examsQuery->whereIn('category', $activePeriods);
            }
            // FIXED: Removed the `else { $examsQuery->whereRaw('1 = 0'); }` 
            // so teachers can see ongoing exams even if 'active_periods' is not configured in settings.
        }

        $exams = $examsQuery->pluck('name', 'id');

        $classesQuery = ClassSection::with('gradeLevel')
            ->where('is_active', true);

        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }

        // STRICT TEACHER/STAFF FILTERING: 
        // If not an admin, they ONLY see classes assigned to them via Timetable or Allocation
        if (!$isAdmin) {
            if ($user->staff) {
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
            } else {
                // Not an admin and has no staff profile = Sees no classes
                $classesQuery->whereRaw('1 = 0');
            }
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
        $isAdmin = $user->hasRole([
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value, 
            RoleEnum::SCHOOL_ADMIN->value 
        ]);

        // STRICT TEACHER/STAFF FILTERING
        if (!$isAdmin) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                $query->where(function($q) use ($staffId) {
                    $q->where('staff_id', $staffId) // Class Teacher
                      ->orWhereHas('timetables', function($t) use ($staffId) { 
                          $t->where('teacher_id', $staffId); // Subject Teacher via Timetable
                      })
                      ->orWhereHas('classSubjects', function($c) use ($staffId) {
                          $c->where('teacher_id', $staffId); // Subject Teacher via Allocation
                      }); 
                });
            } else {
                $query->whereRaw('1 = 0');
            }
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

        $examSessionId = null;
        if ($examId) {
            $exam = Exam::find($examId);
            if ($exam) $examSessionId = $exam->academic_session_id;
        }

        $isAdmin = $user->hasRole([
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value, 
            RoleEnum::SCHOOL_ADMIN->value 
        ]);

        if (!$isAdmin) {
            if ($user->staff) {
                $staffId = $user->staff->id;
                $timetableIds = Timetable::where('teacher_id', $staffId);
                if ($classSectionId) $timetableIds->where('class_section_id', $classSectionId);
                else $timetableIds->whereHas('classSection', fn($q) => $q->where('grade_level_id', $gradeLevelId));
                $ttIds = $timetableIds->pluck('subject_id')->toArray();

                $allocationIds = ClassSubject::where('teacher_id', $staffId);
                if ($classSectionId) $allocationIds->where('class_section_id', $classSectionId);
                else $allocationIds->whereHas('classSection', fn($q) => $q->where('grade_level_id', $gradeLevelId));
                if ($examSessionId) $allocationIds->where('academic_session_id', $examSessionId);
                $allocIds = $allocationIds->pluck('subject_id')->toArray();

                $subjectIds = array_unique(array_merge($ttIds, $allocIds));
                if (empty($subjectIds)) return response()->json([]);

                $query = Subject::with('academicUnit')->whereIn('id', $subjectIds)->where('is_active', true);
            } else {
                return response()->json([]);
            }
        } else {
            // Admin Logic
            if ($classSectionId) {
                $allocationQuery = ClassSubject::where('class_section_id', $classSectionId);
                if ($examSessionId) $allocationQuery->where('academic_session_id', $examSessionId);
                $allocatedIds = $allocationQuery->pluck('subject_id');
                
                if ($allocatedIds->isNotEmpty()) {
                    $query = Subject::with('academicUnit')->whereIn('id', $allocatedIds)->where('is_active', true);
                } else {
                    $query = Subject::with('academicUnit')->where('grade_level_id', $gradeLevelId)->where('is_active', true);
                }
            } else {
                $query = Subject::with('academicUnit')->where('grade_level_id', $gradeLevelId)->where('is_active', true);
            }
        }

        // Fetch Schedule Configs
        $scheduleConfigs = collect();
        if ($examId && $classSectionId) {
            $scheduleConfigs = ExamSchedule::where('exam_id', $examId)
                ->where('class_section_id', $classSectionId)
                ->get()
                ->keyBy('subject_id');
        }

        $formattedSubjects = $query->get()->map(function($subject) use ($classSectionId, $scheduleConfigs) {
            $teacherName = $this->getSubjectTeacher($classSectionId, $subject->id);
            
            // Determine Max Marks
            $maxMarks = $subject->total_marks ?? 100;
            if (isset($scheduleConfigs[$subject->id])) {
                $configMark = $scheduleConfigs[$subject->id]->max_marks;
                if (!is_null($configMark) && $configMark > 0) {
                    $maxMarks = $configMark;
                }
            }
            
            // Format name with UE info if available
            $displayName = $subject->name;
            $ueInfo = '';
            if($subject->academicUnit) {
                $ueInfo = " (" . $subject->academicUnit->code . ")";
                // Optional: Append coefficient info
                if($subject->coefficient > 0) {
                    $ueInfo .= " [Coeff: " . $subject->coefficient . "]";
                }
            }

            return [
                'id' => $subject->id,
                'name' => $displayName . $ueInfo,
                'raw_name' => $subject->name,
                'total_marks' => (float)$maxMarks,
                'teacher_name' => $teacherName,
                'coefficient' => $subject->coefficient ?? 1,
                'ue_code' => $subject->academicUnit?->code ?? null
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
            
        if ($alloc && $alloc->teacher && $alloc->teacher->user) {
            return $alloc->teacher->user->name;
        }

        // 2. Try Timetable
        $tt = Timetable::with('teacher.user')
            ->where('class_section_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
            
        // FIXED: Using null-safe operators (?->) prevents the 500 error if Timetable doesn't exist
        return $tt?->teacher?->user?->name ?? 'N/A';
    }

    private function validateAccess($examId, $classId, $subjectId)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole([
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value, 
            RoleEnum::SCHOOL_ADMIN->value
        ]);

        if ($isAdmin) {
            return true;
        }

        if ($user->staff) {
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
        
        $isAdmin = Auth::user()->hasRole([
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value, 
            RoleEnum::SCHOOL_ADMIN->value
        ]);

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

        // Prioritize schedule max_marks if available
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