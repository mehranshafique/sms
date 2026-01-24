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
                
                // 1. Get Global Subjects for this Grade
                $gradeSubjects = Subject::where('grade_level_id', $selectedClass->grade_level_id)
                    ->where('is_active', true)
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
                    ->mapWithKeys(fn($t) => [$t->id => $t->user->name]);
            }
        }

        return view('class_subjects.manage', compact('classes', 'selectedClass', 'gradeSubjects', 'allocations', 'teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'assignments' => 'array',
        ]);

        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if (!$session) {
            return back()->with('error', __('class_subject.no_active_session'));
        }

        DB::transaction(function () use ($request, $institutionId, $session) {
            
            // Sync Logic: Loop through submitted assignments
            if ($request->has('assignments')) {
                foreach ($request->assignments as $subjectId => $data) {
                    
                    // If "enabled" checkbox is checked
                    if (isset($data['enabled']) && $data['enabled'] == 1) {
                        ClassSubject::updateOrCreate(
                            [
                                'institution_id' => $institutionId,
                                'academic_session_id' => $session->id,
                                'class_section_id' => $request->class_section_id,
                                'subject_id' => $subjectId,
                            ],
                            [
                                'teacher_id' => $data['teacher_id'] ?? null,
                                'weekly_periods' => $data['weekly_periods'] ?? 0,
                                'exam_weight' => $data['exam_weight'] ?? 100,
                            ]
                        );
                    } else {
                        // If unchecked, remove allocation
                        ClassSubject::where('institution_id', $institutionId)
                            ->where('academic_session_id', $session->id)
                            ->where('class_section_id', $request->class_section_id)
                            ->where('subject_id', $subjectId)
                            ->delete();
                    }
                }
            }
        });

        return back()->with('success', __('class_subject.success_update'));
    }
}