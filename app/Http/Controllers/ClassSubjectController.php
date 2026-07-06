<?php

namespace App\Http\Controllers;

use App\Models\ClassSubject;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ClassSubjectController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // Enforce Permissions for Class Subject Configuration
        $this->middleware(PermissionMiddleware::class . ':class_subject.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':class_subject.update')->only(['store']);
        
        $this->setPageTitle(__('class_subject.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $classes = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->get();

        $selectedClass = null;
        $gradeSubjects = collect();
        $allocations = collect();
        $teachers = collect();

        if ($request->has('class_section_id')) {
            $selectedClass = ClassSection::find($request->class_section_id);
            if ($selectedClass && $selectedClass->institution_id == $institutionId) {
                
                // 1. Get Subjects for this Grade (scoped to institution)
                $gradeSubjects = Subject::with('academicUnit')
                    ->where('grade_level_id', $selectedClass->grade_level_id)
                    ->where('institution_id', $institutionId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();

                // 2. Get Existing Allocations
                $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
                if ($session) {
                    $allocations = ClassSubject::where('class_section_id', $selectedClass->id)
                        ->where('academic_session_id', $session->id)
                        ->get()
                        ->keyBy('subject_id');
                }

                // 3. Teachers
                $teachers = Staff::with('user')
                    ->where('institution_id', $institutionId)
                    ->get()
                    ->mapWithKeys(function ($t) {
                        $name = $t->user->name ?? trim(($t->first_name ?? '') . ' ' . ($t->last_name ?? ''));
                        return $name !== '' ? [$t->id => $name] : [];
                    });
            }
        }

        return view('class_subjects.manage', compact('classes', 'selectedClass', 'gradeSubjects', 'allocations', 'teachers'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'assignments' => 'array',
        ]);

        $classSection = ClassSection::find($request->class_section_id);
        if (!$classSection || (int) $classSection->institution_id !== (int) $institutionId) {
            return back()->with('error', __('class_subject.invalid_class'));
        }

        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if (!$session) {
            return back()->with('error', __('class_subject.no_active_session'));
        }

        try {
            DB::transaction(function () use ($request, $institutionId, $session) {
                if ($request->has('assignments')) {
                    foreach ($request->assignments as $subjectId => $data) {
                        if (isset($data['enabled']) && $data['enabled'] == 1) {
                            $teacherId = $data['teacher_id'] ?? null;
                            $teacherId = ($teacherId === '' || $teacherId === null) ? null : (int) $teacherId;

                            ClassSubject::updateOrCreate(
                                [
                                    'institution_id' => $institutionId,
                                    'academic_session_id' => $session->id,
                                    'class_section_id' => $request->class_section_id,
                                    'subject_id' => $subjectId,
                                ],
                                [
                                    'teacher_id' => $teacherId,
                                    'weekly_periods' => (int) ($data['weekly_periods'] ?? 0),
                                    'exam_weight' => (float) ($data['exam_weight'] ?? 100),
                                ]
                            );
                        } else {
                            ClassSubject::where('institution_id', $institutionId)
                                ->where('academic_session_id', $session->id)
                                ->where('class_section_id', $request->class_section_id)
                                ->where('subject_id', $subjectId)
                                ->delete();
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', __('class_subject.save_failed'));
        }

        return redirect()
            ->route('class-subjects.index', ['class_section_id' => $request->class_section_id])
            ->with('success', __('class_subject.success_update'));
    }
}