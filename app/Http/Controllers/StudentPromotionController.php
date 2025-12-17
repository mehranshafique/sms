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
        $institutionId = Auth::user()->institute_id;

        // 1. Fetch Academic Sessions (Source and Target)
        // Fix: Handle Super Admin (null institute_id) by fetching all or filtering if selected
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

        // 2. Fetch Classes
        $classesQuery = ClassSection::with('institution');
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        
        $classes = $classesQuery->get()->mapWithKeys(function ($item) use ($institutionId) {
            $label = $item->name;
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }
            return [$item->id => $label];
        });

        $students = [];
        
        // 3. If "Filter" is applied, fetch eligible students
        if ($request->filled('from_session_id') && $request->filled('from_class_id')) {
            $students = StudentEnrollment::with('student')
                ->where('academic_session_id', $request->from_session_id)
                ->where('class_section_id', $request->from_class_id)
                ->where('status', 'active') 
                ->get();
        }

        return view('promotions.index', compact('sessions', 'classes', 'students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_session_id' => 'required|exists:academic_sessions,id',
            'from_class_id'   => 'required|exists:class_sections,id',
            'to_session_id'   => 'required|exists:academic_sessions,id|different:from_session_id',
            'to_class_id'     => 'required|exists:class_sections,id',
            'promote'         => 'required|array',
            'promote.*'       => 'exists:students,id',
        ]);

        DB::transaction(function () use ($request) {
            // Determine Institution ID from the target session
            $targetSession = AcademicSession::findOrFail($request->to_session_id);
            $institutionId = $targetSession->institution_id;
            
            $targetClass = ClassSection::with('gradeLevel')->findOrFail($request->to_class_id);

            foreach ($request->promote as $studentId) {
                // 1. Mark old enrollment as 'promoted'
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
                        'institution_id'      => $institutionId,
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