<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\StudentEnrollment;
use App\Models\InstitutionSetting;
use App\Enums\RoleEnum; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ExamScheduleController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('exam_schedule.title'));
    }

    /**
     * View Datesheets (Student / Teacher / Parent)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();
        
        $schedules = collect();
        $selectedClass = null;
        $classes = [];

        // 1. STUDENT VIEW
        if ($user->hasRole(RoleEnum::STUDENT->value)) {
            $student = $user->student;
            if ($student) {
                // Get active enrollment
                $enrollment = $student->enrollments()
                    ->where('status', 'active')
                    ->latest('created_at')
                    ->first();
                
                if ($enrollment) {
                    $selectedClass = $enrollment->classSection;
                    $schedules = ExamSchedule::with(['exam', 'subject'])
                        ->where('class_section_id', $enrollment->class_section_id)
                        ->whereHas('exam', function($q) use ($enrollment) {
                            $q->where('academic_session_id', $enrollment->academic_session_id)
                              ->where('status', '!=', 'completed'); // Show ongoing/scheduled
                        })
                        ->orderBy('exam_date')
                        ->orderBy('start_time')
                        ->get()
                        ->groupBy('exam.name');
                }
            }
        }
        // 2. TEACHER / ADMIN VIEW
        else {
            // Fetch Classes for Filter
            $classes = ClassSection::with('gradeLevel')
                ->where('institution_id', $institutionId)
                ->get()
                ->mapWithKeys(function ($item) {
                    $name = ($item->gradeLevel->name ?? 'N/A') . ' ' . $item->name;
                    return [$item->id => $name];
                });

            if ($request->class_section_id) {
                $selectedClass = ClassSection::find($request->class_section_id);
                if ($selectedClass) {
                    $schedules = ExamSchedule::with(['exam', 'subject'])
                        ->where('class_section_id', $request->class_section_id)
                        ->orderBy('exam_date')
                        ->orderBy('start_time')
                        ->get()
                        ->groupBy('exam.name');
                }
            }
        }

        return view('exams.schedules.index', compact('schedules', 'classes', 'selectedClass'));
    }

    public function manage(Request $request)
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();

        $exams = Exam::where('institution_id', $institutionId)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->latest()
            ->pluck('name', 'id');

        // Fetch Classes with Grade Level for Display: "Grade 1 Section A" (No Hyphen)
        $classes = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->get()
            ->mapWithKeys(function ($item) {
                $name = ($item->gradeLevel->name ?? 'N/A') . ' ' . $item->name;
                return [$item->id => $name];
            });

        return view('exams.schedules.manage', compact('exams', 'classes'));
    }

    public function getSubjects(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        $classSection = ClassSection::findOrFail($request->class_section_id);

        // 1. Fetch Subjects linked to the Grade Level of the selected Section
        $subjects = Subject::where('grade_level_id', $classSection->grade_level_id)
            ->where('is_active', true)
            ->get();

        if ($subjects->isEmpty()) {
            return response()->json(['message' => __('exam_schedule.no_subjects_found')], 404);
        }

        // 2. Fetch Existing Schedules
        $existingSchedules = ExamSchedule::where('exam_id', $exam->id)
            ->where('class_section_id', $classSection->id)
            ->get()
            ->keyBy('subject_id');

        // 3. Prepare Data
        $rows = $subjects->map(function ($subject) use ($existingSchedules, $exam) {
            $schedule = $existingSchedules->get($subject->id);
            
            return [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'subject_code' => $subject->code,
                'date' => $schedule ? $schedule->exam_date->format('Y-m-d') : '',
                'start_time' => $schedule ? $schedule->start_time->format('H:i') : '',
                'end_time' => $schedule ? $schedule->end_time->format('H:i') : '',
                'room_number' => $schedule->room_number ?? '',
                'max_marks' => $schedule->max_marks ?? $subject->total_marks ?? 100,
                'pass_marks' => $schedule->pass_marks ?? $subject->passing_marks ?? 33,
                'is_scheduled' => $schedule ? true : false,
            ];
        });

        return response()->json([
            'exam_start' => $exam->start_date->format('Y-m-d'),
            'exam_end' => $exam->end_date->format('Y-m-d'),
            'rows' => $rows
        ]);
    }

    /**
     * Helper to auto-generate a schedule proposal
     * UPDATED: Better looping logic to ensure maximum coverage
     */
    public function autoGenerate(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
        ]);

        $institutionId = $this->getInstitutionId();
        $exam = Exam::findOrFail($request->exam_id);
        $classSection = ClassSection::findOrFail($request->class_section_id);

        // 1. Get School Timings
        $settings = InstitutionSetting::where('institution_id', $institutionId)
            ->whereIn('key', ['school_start_time', 'school_end_time'])
            ->pluck('value', 'key');

        $schoolStart = $settings['school_start_time'] ?? '08:00';
        $schoolEnd = $settings['school_end_time'] ?? '14:00';

        $defaultDurationMinutes = 120; // 2 hours duration
        $gapMinutes = 30; // Gap between exams on the same day
        
        // 2. Get Subjects
        $subjects = Subject::where('grade_level_id', $classSection->grade_level_id)
            ->where('is_active', true)
            ->get();

        $scheduleProposal = [];
        
        // 3. Smart Date Logic
        $examStartDate = Carbon::parse($exam->start_date)->startOfDay();
        $examEndDate = Carbon::parse($exam->end_date)->startOfDay();
        $today = Carbon::now()->startOfDay();

        // Start scheduling from Tomorrow if exam started in past, else from Start Date
        if ($examStartDate->lte($today)) {
            $currentDate = $today->copy()->addDay();
        } else {
            $currentDate = $examStartDate;
        }

        // Ensure we don't start AFTER the end date (edge case)
        if ($currentDate->gt($examEndDate)) {
            $currentDate = $examEndDate; 
        }

        // Initialize time slot tracking
        $currentStartTime = Carbon::parse($schoolStart);
        $maxEndTime = Carbon::parse($schoolEnd);

        foreach ($subjects as $subject) {
            // Check if current date is valid (not Sunday)
            while ($currentDate->isSunday()) {
                $currentDate->addDay();
                // Reset time for new day
                $currentStartTime = Carbon::parse($schoolStart);
            }

            // Calculate potential end time for this slot
            $proposedEndTime = $currentStartTime->copy()->addMinutes($defaultDurationMinutes);

            // If the exam goes beyond school hours, move to next day
            if ($proposedEndTime->format('H:i') > $maxEndTime->format('H:i')) {
                $currentDate->addDay();
                // Re-check Sunday for the new day
                while ($currentDate->isSunday()) {
                    $currentDate->addDay();
                }
                // Reset time to start of day
                $currentStartTime = Carbon::parse($schoolStart);
                $proposedEndTime = $currentStartTime->copy()->addMinutes($defaultDurationMinutes);
            }

            // If we've run past the exam end date, just use the end date (cram them in)
            // Note: If we are cramming on the last day, time logic might still push beyond school hours
            // but we will allow it for now to ensure all subjects get a slot.
            $dateToUse = $currentDate->gt($examEndDate) ? $examEndDate : $currentDate;

            $scheduleProposal[$subject->id] = [
                'date' => $dateToUse->format('Y-m-d'),
                'start_time' => $currentStartTime->format('H:i'),
                'end_time' => $proposedEndTime->format('H:i'),
            ];

            // Prepare start time for the *next* subject on the SAME day
            $currentStartTime = $proposedEndTime->copy()->addMinutes($gapMinutes);
            
            // Note: We do NOT increment the day here. The loop's next iteration will check 
            // if the updated $currentStartTime fits within school hours. If not, it increments the day then.
        }

        return response()->json([
            'message' => __('exam_schedule.auto_fill_success'),
            'schedule' => $scheduleProposal
        ]);
    }

    public function getStudents(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
        ]);

        $students = StudentEnrollment::where('class_section_id', $request->class_section_id)
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->student->id,
                    'text' => $enrollment->student->full_name . ' (' . $enrollment->student->admission_number . ')',
                ];
            });

        return response()->json($students);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'schedules' => 'required|array',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if (!$user->hasRole(['Super Admin', 'Head Officer'])) {
            $isLocked = InstitutionSetting::get($institutionId, 'exams_locked', 0);
            if ($isLocked) {
                return response()->json(['message' => __('settings.admin_blocked_error')], 403);
            }
        }

        DB::beginTransaction();

        try {
            foreach ($request->schedules as $subjectId => $data) {
                if (empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
                    continue;
                }

                $date = $data['date'];
                $start = $data['start_time'];
                $end = $data['end_time'];
                $room = $data['room_number'] ?? null;
                $maxMarks = $data['max_marks'] ?? null;
                $passMarks = $data['pass_marks'] ?? null;

                if ($date < $exam->start_date->format('Y-m-d') || $date > $exam->end_date->format('Y-m-d')) {
                    throw new \Exception(__('exam_schedule.error_date_range', [
                        'date' => $date, 
                        'start' => $exam->start_date->format('d-M'), 
                        'end' => $exam->end_date->format('d-M')
                    ]));
                }

                $classConflict = ExamSchedule::where('institution_id', $institutionId)
                    ->where('class_section_id', $request->class_section_id)
                    ->where('exam_date', $date)
                    ->where('id', '!=', $data['id'] ?? 0)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_time', [$start, $end])
                          ->orWhereBetween('end_time', [$start, $end])
                          ->orWhere(function ($sq) use ($start, $end) {
                              $sq->where('start_time', '<=', $start)
                                 ->where('end_time', '>=', $end);
                          });
                    })
                    ->exists();

                if ($classConflict) {
                    throw new \Exception(__('exam_schedule.error_overlap_class', [
                        'date' => $date, 'start' => $start, 'end' => $end
                    ]));
                }

                if ($room) {
                    $roomConflict = ExamSchedule::where('institution_id', $institutionId)
                        ->where('room_number', $room)
                        ->where('exam_date', $date)
                        ->where('id', '!=', $data['id'] ?? 0)
                        ->where(function ($q) use ($start, $end) {
                            $q->whereBetween('start_time', [$start, $end])
                              ->orWhereBetween('end_time', [$start, $end])
                              ->orWhere(function ($sq) use ($start, $end) {
                                  $sq->where('start_time', '<=', $start)
                                     ->where('end_time', '>=', $end);
                              });
                        })
                        ->exists();

                    if ($roomConflict) {
                        throw new \Exception(__('exam_schedule.error_overlap_room', [
                            'room' => $room, 'date' => $date, 'start' => $start, 'end' => $end
                        ]));
                    }
                }

                ExamSchedule::updateOrCreate(
                    [
                        'institution_id' => $institutionId,
                        'exam_id' => $exam->id,
                        'class_section_id' => $request->class_section_id,
                        'subject_id' => $subjectId,
                    ],
                    [
                        'exam_date' => $date,
                        'start_time' => $start,
                        'end_time' => $end,
                        'room_number' => $room,
                        'max_marks' => $maxMarks,
                        'pass_marks' => $passMarks,
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => __('exam_schedule.success_saved')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function downloadAdmitCards(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'student_id' => 'nullable|exists:students,id',
        ]);

        $exam = Exam::with('institution')->findOrFail($request->exam_id);
        $classSection = ClassSection::with('gradeLevel')->findOrFail($request->class_section_id);

        $schedules = ExamSchedule::with('subject')
            ->where('exam_id', $exam->id)
            ->where('class_section_id', $classSection->id)
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return back()->with('error', __('exam_schedule.no_schedules_found'));
        }

        $studentsQuery = StudentEnrollment::with(['student.parent'])
            ->where('class_section_id', $classSection->id)
            ->where('academic_session_id', $exam->academic_session_id)
            ->where('status', 'active');

        if ($request->student_id) {
            $studentsQuery->where('student_id', $request->student_id);
        }

        $students = $studentsQuery->orderBy('roll_number')
            ->get()
            ->map(function ($enrollment) {
                $student = $enrollment->student;
                $student->current_roll_no = $enrollment->roll_number; 
                return $student;
            });

        if ($students->isEmpty()) {
            return back()->with('error', __('student.no_records_found'));
        }

        $pdf = Pdf::loadView('exams.admit_card', compact('exam', 'classSection', 'schedules', 'students'))
            ->setPaper('a4', 'portrait');

        $fileName = 'Admit_Cards_' . $classSection->name . '_' . $exam->name . '.pdf';
        if ($request->student_id && $students->count() == 1) {
            $fileName = 'Admit_Card_' . $students->first()->full_name . '.pdf';
        }
        
        // --- NEW: Support for Preview Action ---
        if ($request->has('preview') && $request->preview == '1') {
            return $pdf->stream($fileName);
        }
        
        return $pdf->download($fileName);
    }
}