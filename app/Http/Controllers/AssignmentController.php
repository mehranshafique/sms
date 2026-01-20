<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\AcademicSession;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Enums\RoleEnum;

class AssignmentController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('assignment.page_title'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();
        
        $query = Assignment::with(['classSection.gradeLevel', 'subject', 'teacher.user'])
            ->where('institution_id', $institutionId)
            ->latest();

        // Role-based filtering
        if ($user->hasRole(RoleEnum::STUDENT->value)) {
            $student = $user->student;
            if ($student) {
                $currentClassId = $student->enrollments()
                    ->where('status', 'active')
                    ->latest('created_at')
                    ->value('class_section_id');
                
                if ($currentClassId) {
                    $query->where('class_section_id', $currentClassId);
                } else {
                    $query->whereRaw('1 = 0'); 
                }
            }
            // Return Specific Student View
            $assignments = $query->paginate(10);
            return view('assignments.student_index', compact('assignments'));
        } 
        
        elseif ($user->hasRole(RoleEnum::TEACHER->value)) {
            $staff = $user->staff;
            if ($staff) {
                $query->where('teacher_id', $staff->id);
            }
        }

        $assignments = $query->paginate(10);

        return view('assignments.index', compact('assignments'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        $classesQuery = ClassSection::with('gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('is_active', true);

        if ($user->hasRole(RoleEnum::TEACHER->value) && $user->staff) {
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

        return view('assignments.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'deadline' => 'required|date|after_or_equal:today',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if (!$session) {
            return back()->with('error', __('assignment.no_active_session'));
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('assignments', 'public');
        }

        Assignment::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $session->id,
            'class_section_id' => $request->class_section_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => Auth::user()->staff->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'file_path' => $filePath,
        ]);

        return redirect()->route('assignments.index')->with('success', __('assignment.success_create'));
    }

    public function getSubjects(Request $request)
    {
        $request->validate(['class_section_id' => 'required|exists:class_sections,id']);
        
        $user = Auth::user();
        $section = ClassSection::find($request->class_section_id);
        
        if (!$section) return response()->json([]);

        if ($user->hasRole(RoleEnum::TEACHER->value) && $user->staff) {
            $staffId = $user->staff->id;
            
            $subjectIds = Timetable::where('teacher_id', $staffId)
                ->where('class_section_id', $section->id)
                ->pluck('subject_id')
                ->unique()
                ->toArray();

            if (empty($subjectIds)) return response()->json([]);

            $subjects = Subject::whereIn('id', $subjectIds)->where('is_active', true)->get();
        } else {
            $subjects = Subject::where('grade_level_id', $section->grade_level_id)
                ->where('is_active', true)
                ->get();
        }

        $formatted = $subjects->map(function($s) {
            return ['id' => $s->id, 'name' => $s->name];
        });

        return response()->json($formatted);
    }
    
    public function destroy(Assignment $assignment)
    {
        if ($assignment->file_path) {
            Storage::disk('public')->delete($assignment->file_path);
        }
        $assignment->delete();

        // Support AJAX SweetAlert delete
        if (request()->ajax()) {
            return response()->json(['message' => __('assignment.success_delete')]);
        }

        return back()->with('success', __('assignment.success_delete'));
    }
}