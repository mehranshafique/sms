<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use App\Models\Student;
use App\Models\ClassSection; // Used as 'Program'
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware; // Import Middleware
use Illuminate\Support\Facades\DB;

class UniversityEnrollmentController extends BaseController
{
    public function __construct()
    {
        // Use correct permission keys matching the seeder
        $this->middleware(PermissionMiddleware::class . ':university_enrollment.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':university_enrollment.create')->only(['create', 'store']);
        $this->middleware(PermissionMiddleware::class . ':university_enrollment.update')->only(['edit', 'update']);
        $this->middleware(PermissionMiddleware::class . ':university_enrollment.delete')->only(['destroy']);
        
        $this->setPageTitle(__('university_enrollment.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $querySession = AcademicSession::query();
        if($institutionId) {
            $querySession->where('institution_id', $institutionId);
        }
        $currentSession = $querySession->where('is_current', true)->first();

        if ($request->ajax()) {
            $data = StudentEnrollment::with(['student', 'classSection.gradeLevel'])
                ->whereHas('classSection.gradeLevel', function($q) {
                    $q->whereIn('education_cycle', ['university', 'lmd']);
                })
                ->select('student_enrollments.*')
                ->latest('created_at');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }
            if ($currentSession) {
                $data->where('academic_session_id', $currentSession->id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('student_name', fn($row) => $row->student->full_name ?? 'N/A')
                ->addColumn('admission_no', fn($row) => $row->student->admission_number ?? '-')
                ->addColumn('program', function($row){
                    return $row->classSection->name ?? 'N/A';
                })
                ->addColumn('level', function($row){
                    return $row->classSection->gradeLevel->name ?? '-';
                })
                ->editColumn('status', function($row){
                    $badges = ['active'=>'success', 'left'=>'warning', 'graduated'=>'info'];
                    return '<span class="badge badge-'.($badges[$row->status]??'secondary').'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end">';
                    if(auth()->user()->can('university_enrollment.update')){
                        $btn .= '<a href="'.route('university.enrollments.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('university_enrollment.delete')){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('university.enrollments.index');
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        // Filter Classes (Programs) that are University Cycle
        $programsQuery = ClassSection::whereHas('gradeLevel', function($q){
            $q->whereIn('education_cycle', ['university', 'lmd']);
        })->with('gradeLevel');

        if ($institutionId) {
            $programsQuery->where('institution_id', $institutionId);
        }
        
        $programs = $programsQuery->get()->mapWithKeys(function($item){
            return [$item->id => $item->name . ' (' . $item->gradeLevel->name . ')'];
        });

        // Get Active Session
        $session = AcademicSession::where('is_current', true);
        if($institutionId) $session->where('institution_id', $institutionId);
        $currentSession = $session->first();
        $sessionId = $currentSession ? $currentSession->id : 0;

        // Filter Students not yet enrolled in THIS session
        $studentsQuery = Student::whereDoesntHave('enrollments', function($q) use ($sessionId) {
            $q->where('academic_session_id', $sessionId);
        });
        
        if ($institutionId) {
            $studentsQuery->where('institution_id', $institutionId);
        }

        $students = $studentsQuery->select('id', 'first_name', 'last_name', 'admission_number')
            ->get()
            ->mapWithKeys(function($item){
                return [$item->id => $item->full_name . ' (' . $item->admission_number . ')'];
            });

        return view('university.enrollments.create', compact('programs', 'students'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        // Ensure Class is University Type
        $program = ClassSection::with('gradeLevel')->findOrFail($request->class_section_id);
        if(!in_array($program->gradeLevel->education_cycle, ['university', 'lmd'])) {
             return response()->json(['message' => 'Invalid program type for this module.'], 422);
        }

        $session = AcademicSession::where('institution_id', $program->institution_id)->where('is_current', true)->first();
        if(!$session) return response()->json(['message' => __('university_enrollment.no_active_session')], 422);

        $request->validate([
            'student_ids' => 'required|array', // Bulk IDs
            'student_ids.*' => 'exists:students,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'status' => 'required|in:active,left,graduated',
            'enrolled_at' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $program, $session) {
            foreach ($request->student_ids as $studentId) {
                // Prevent duplicate enrollment for the same student in the same session
                $exists = StudentEnrollment::where('academic_session_id', $session->id)
                    ->where('student_id', $studentId)
                    ->exists();

                if (!$exists) {
                    StudentEnrollment::create([
                        'institution_id' => $program->institution_id,
                        'academic_session_id' => $session->id,
                        'student_id' => $studentId,
                        'class_section_id' => $request->class_section_id,
                        'grade_level_id' => $program->grade_level_id,
                        'roll_number' => null, // or custom logic
                        'status' => $request->status,
                        'enrolled_at' => $request->enrolled_at
                    ]);
                }
            }
        });

        return response()->json(['message' => __('university_enrollment.success_create'), 'redirect' => route('university.enrollments.index')]);
    }

    public function edit($id)
    {
        $enrollment = StudentEnrollment::findOrFail($id);
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $enrollment->institution_id != $institutionId) abort(403);

        $programsQuery = ClassSection::whereHas('gradeLevel', function($q){
            $q->whereIn('education_cycle', ['university', 'lmd']);
        })->with('gradeLevel');

        if ($institutionId) $programsQuery->where('institution_id', $institutionId);
        
        $programs = $programsQuery->get()->mapWithKeys(function($item){
            return [$item->id => $item->name . ' (' . $item->gradeLevel->name . ')'];
        });

        $students = [$enrollment->student_id => $enrollment->student->full_name];

        return view('university.enrollments.edit', compact('enrollment', 'programs', 'students'));
    }

    public function update(Request $request, $id)
    {
        $enrollment = StudentEnrollment::findOrFail($id);
        
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'status' => 'required|in:active,left,graduated',
            'enrolled_at' => 'required|date',
        ]);

        $program = ClassSection::find($request->class_section_id);

        $enrollment->update([
            'class_section_id' => $request->class_section_id,
            'grade_level_id' => $program->grade_level_id,
            'roll_number' => $request->roll_number,
            'status' => $request->status,
            'enrolled_at' => $request->enrolled_at
        ]);

        return response()->json(['message' => __('university_enrollment.success_update'), 'redirect' => route('university.enrollments.index')]);
    }

    public function destroy($id)
    {
        $enrollment = StudentEnrollment::findOrFail($id);
        $enrollment->delete();
        return response()->json(['message' => __('university_enrollment.success_delete')]);
    }
}