<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\StudentEnrollment;
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

        // 1. Fetch Exams (Scheduled or Ongoing)
        // Fix: If Super Admin (null ID), fetch ALL exams. Otherwise filter by institute.
        $examsQuery = Exam::whereIn('status', ['scheduled', 'ongoing']);
        if ($institutionId) {
            $examsQuery->where('institution_id', $institutionId);
        }
        $exams = $examsQuery->pluck('name', 'id');

        // 2. Fetch Classes
        $classesQuery = ClassSection::query();
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        $classes = $classesQuery->pluck('name', 'id');

        $subjects = [];
        $students = [];
        $existingMarks = [];

        // 3. Logic for Dependent Dropdowns
        // If Class is selected, fetch Subjects for that class's Grade Level
        if ($request->filled('class_section_id')) {
             $selectedClass = ClassSection::find($request->class_section_id);
             if($selectedClass) {
                 $subjects = Subject::where('grade_level_id', $selectedClass->grade_level_id)->pluck('name', 'id');
             }
        }

        // 4. If ALL criteria selected, fetch Students & Marks
        if ($request->filled('exam_id') && $request->filled('class_section_id') && $request->filled('subject_id')) {
            
            $exam = Exam::find($request->exam_id);
            
            if ($exam) {
                // Fetch Enrolled Students for this Session & Class
                $students = StudentEnrollment::with('student')
                    ->where('class_section_id', $request->class_section_id)
                    ->where('academic_session_id', $exam->academic_session_id)
                    ->where('status', 'active')
                    ->get();

                // Fetch Existing Marks
                $existingMarks = ExamRecord::where('exam_id', $request->exam_id)
                    ->where('subject_id', $request->subject_id)
                    ->where('class_section_id', $request->class_section_id)
                    ->get()
                    ->keyBy('student_id');
            }
        }

        return view('marks.create', compact('exams', 'classes', 'subjects', 'students', 'existingMarks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks' => 'required|array',
            'marks.*' => 'numeric|min:0',
            'absent' => 'nullable|array',
        ]);

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

        // Redirect back with query params to keep the form populated
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