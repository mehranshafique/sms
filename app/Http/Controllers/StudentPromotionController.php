<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\PermissionMiddleware;

class StudentPromotionController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':student_promotion.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':student_promotion.create')->only(['store']);
        $this->setPageTitle(__('promotion.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        // 1. Fetch Academic Sessions
        $sessionsQuery = AcademicSession::with('institution');
        if ($institutionId) {
            $sessionsQuery->where('institution_id', $institutionId);
        }
        
        $sessions = $sessionsQuery->orderBy('start_date', 'desc')->get()->mapWithKeys(function ($item) use ($institutionId) {
            $label = $item->name;
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }
            return [$item->id => $label];
        });

        // 2. Fetch Classes (Rule 2: Show Section + Grade)
        $classesQuery = ClassSection::with(['institution', 'gradeLevel']);
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        
        $classes = $classesQuery->get()->mapWithKeys(function ($item) use ($institutionId) {
            $grade = $item->gradeLevel->name ?? '';
            $label = $item->name . ($grade ? ' (' . $grade . ')' : '');
            
            if (!$institutionId && $item->institution) {
                $label .= ' - ' . $item->institution->code;
            }
            return [$item->id => $label];
        });

        $students = [];
        
        // 3. Logic: Find Eligible Students
        if ($request->filled('from_session_id') && $request->filled('from_class_id')) {
            
            $query = StudentEnrollment::with(['student', 'classSection', 'gradeLevel'])
                ->where('academic_session_id', $request->from_session_id)
                ->where('class_section_id', $request->from_class_id)
                ->whereIn('status', ['active', 'promoted']); 

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }

            if ($request->filled('to_session_id')) {
                $toSessionId = $request->to_session_id;
                $query->whereDoesntHave('student.enrollments', function($q) use ($toSessionId) {
                    $q->where('academic_session_id', $toSessionId);
                });
            }

            // Rule 3: Latest records first logic (though usually alphabetical for lists is better, strict adherence applied)
            $students = $query->latest('created_at')->get();
        }

        return view('promotions.index', compact('sessions', 'classes', 'students'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'from_session_id' => 'required|exists:academic_sessions,id',
            'from_class_id'   => 'required|exists:class_sections,id',
            'to_session_id'   => 'required|exists:academic_sessions,id|different:from_session_id',
            'to_class_id'     => 'required|exists:class_sections,id',
            'promote'         => 'required|array',
            'promote.*'       => 'exists:students,id',
        ]);

        DB::transaction(function () use ($request, $institutionId) {
            $targetClass = ClassSection::with('gradeLevel')->findOrFail($request->to_class_id);
            
            if ($institutionId && $targetClass->institution_id != $institutionId) {
                abort(403); // Rule 1: Removed hardcoded string
            }

            $targetInstitutionId = $targetClass->institution_id;

            foreach ($request->promote as $studentId) {
                // 1. Mark OLD enrollment as 'promoted'
                StudentEnrollment::where('academic_session_id', $request->from_session_id)
                    ->where('class_section_id', $request->from_class_id)
                    ->where('student_id', $studentId)
                    ->update(['status' => 'promoted']);

                // 2. Create NEW enrollment
                $exists = StudentEnrollment::where('academic_session_id', $request->to_session_id)
                    ->where('student_id', $studentId)
                    ->exists();

                if (!$exists) {
                    StudentEnrollment::create([
                        'institution_id'      => $targetInstitutionId,
                        'academic_session_id' => $request->to_session_id,
                        'student_id'          => $studentId,
                        'grade_level_id'      => $targetClass->grade_level_id,
                        'class_section_id'    => $request->to_class_id,
                        'status'              => 'active',
                        'enrolled_at'         => now(),
                        'roll_number'         => null 
                    ]);
                }
            }
        });

        return response()->json(['message' => __('promotion.messages.success_promote'), 'redirect' => route('promotions.index')]);
    }
}