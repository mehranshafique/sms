<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use App\Models\Student;
use App\Models\ClassSection;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\PermissionMiddleware;

class StudentEnrollmentController extends BaseController
{
    public function __construct()
    {
        // Replace authorizeResource with explicit middleware to match Seeder permissions (enrollment.*)
        // Seeder uses 'Enrollments' -> 'enrollment' prefix
        
        $this->middleware(PermissionMiddleware::class . ':enrollment.viewAny')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':enrollment.create')->only(['create', 'store']);
        $this->middleware(PermissionMiddleware::class . ':enrollment.update')->only(['edit', 'update']);
        $this->middleware(PermissionMiddleware::class . ':enrollment.delete')->only(['destroy', 'bulkDelete']);
        
        $this->setPageTitle(__('enrollment.page_title'));
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
            $data = StudentEnrollment::with(['student', 'classSection.gradeLevel', 'gradeLevel'])
                ->select('student_enrollments.*')
                ->latest('student_enrollments.created_at');

            if ($institutionId) {
                $data->where('student_enrollments.institution_id', $institutionId);
            }

            if ($currentSession) {
                $data->where('student_enrollments.academic_session_id', $currentSession->id);
            }

            if ($request->has('class_section_id') && $request->class_section_id) {
                $data->where('student_enrollments.class_section_id', $request->class_section_id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    if(auth()->user()->can('enrollment.delete')){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('student_name', function($row){
                    return $row->student->full_name ?? 'N/A';
                })
                ->addColumn('student_code', function($row){
                    // UPDATED: Explicitly use admission_number instead of any ID
                    return $row->student->admission_number ?? '-';
                })
                ->addColumn('class', function($row){
                    $grade = $row->classSection->gradeLevel->name ?? '';
                    return ($row->classSection->name ?? 'N/A') . ($grade ? ' (' . $grade . ')' : '');
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'active' => 'badge-success',
                        'promoted' => 'badge-info',
                        'detained' => 'badge-danger',
                        'left' => 'badge-warning',
                    ];
                    $class = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge '.$class.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    if(auth()->user()->can('enrollment.update')){
                        $btn .= '<a href="'.route('enrollments.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('enrollment.delete')){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        $classSectionsQuery = ClassSection::with('gradeLevel');
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) {
             $grade = $item->gradeLevel->name ?? '';
             return [$item->id => $item->name . ($grade ? ' (' . $grade . ')' : '')];
        });
        
        $sessionName = $currentSession ? $currentSession->name : __('enrollment.no_active_session');

        return view('enrollments.index', compact('classSections', 'sessionName'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        // 1. Get Classes (Target)
        $classesQuery = ClassSection::with(['gradeLevel', 'institution']);
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        $classes = $classesQuery->get()->mapWithKeys(function($item) use ($institutionId){
            $label = $item->name . ' (' . ($item->gradeLevel->name ?? '') . ')';
            if (!$institutionId && $item->institution) {
                $label .= ' - ' . $item->institution->code;
            }
            return [$item->id => $label];
        });

        // 2. Get Current Session
        $querySession = AcademicSession::where('is_current', true);
        if($institutionId) {
            $querySession->where('institution_id', $institutionId);
        }
        $currentSession = $querySession->first();
        $sessionId = $currentSession ? $currentSession->id : 0;

        // 3. Get Students NOT enrolled in the current session
        // Only fetch students belonging to this institution
        $studentsQuery = Student::whereDoesntHave('enrollments', function($q) use ($sessionId) {
            $q->where('academic_session_id', $sessionId);
        });
        
        if ($institutionId) {
            $studentsQuery->where('institution_id', $institutionId);
        }

        // Return ID => Name mapping
        $students = $studentsQuery->select('id', 'first_name', 'last_name', 'admission_number')
            ->get()
            ->mapWithKeys(function($item){
                return [$item->id => $item->full_name . ' (' . $item->admission_number . ')'];
            });

        return view('enrollments.create', compact('classes', 'students'));
    }

    public function store(Request $request)
    {
        $userInstituteId = $this->getInstitutionId();
        
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'student_ids' => 'required|array', // CHANGED: Expecting array of IDs
            'student_ids.*' => 'exists:students,id',
            'status' => 'required|in:active,promoted,detained,left',
            'enrolled_at' => 'required|date',
        ]);

        $classSection = ClassSection::with('gradeLevel')->findOrFail($request->class_section_id);
        $targetInstituteId = $classSection->institution_id;

        if ($userInstituteId && $targetInstituteId != $userInstituteId) {
            abort(403);
        }

        $currentSession = AcademicSession::where('institution_id', $targetInstituteId)
            ->where('is_current', true)
            ->first();

        if(!$currentSession) {
            return response()->json(['message' => __('enrollment.no_active_session_error')], 422);
        }

        DB::transaction(function () use ($request, $classSection, $targetInstituteId, $currentSession) {
            foreach ($request->student_ids as $studentId) {
                // Check if already enrolled in this session to prevent duplicates
                $exists = StudentEnrollment::where('academic_session_id', $currentSession->id)
                    ->where('student_id', $studentId)
                    ->exists();

                if (!$exists) {
                    StudentEnrollment::create([
                        'institution_id' => $targetInstituteId,
                        'academic_session_id' => $currentSession->id,
                        'student_id' => $studentId,
                        'grade_level_id' => $classSection->grade_level_id,
                        'class_section_id' => $classSection->id,
                        'status' => $request->status,
                        'enrolled_at' => $request->enrolled_at,
                        // Roll number can be null or auto-generated logic if needed
                        'roll_number' => null 
                    ]);
                }
            }
        });

        return response()->json(['message' => __('enrollment.messages.success_create'), 'redirect' => route('enrollments.index')]);
    }

    public function edit(StudentEnrollment $enrollment)
    {
        $institutionId = $this->getInstitutionId();
        
        if ($institutionId && $enrollment->institution_id != $institutionId) {
            abort(403);
        }
        
        $classesQuery = ClassSection::with('gradeLevel');
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        $classes = $classesQuery->get()->mapWithKeys(function($item){
            return [$item->id => $item->name . ' (' . ($item->gradeLevel->name ?? '') . ')'];
        });

        $students = [$enrollment->student_id => $enrollment->student->full_name];

        return view('enrollments.edit', compact('enrollment', 'classes', 'students'));
    }

    public function update(Request $request, StudentEnrollment $enrollment)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $enrollment->institution_id != $institutionId) {
            abort(403);
        }

        $validated = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'roll_number'      => [
                'nullable', 'string', 'max:20',
                Rule::unique('student_enrollments')
                    ->ignore($enrollment->id)
                    ->where('academic_session_id', $enrollment->academic_session_id)
                    ->where('class_section_id', $request->class_section_id)
            ],
            'status'           => 'required|in:active,promoted,detained,left',
            'enrolled_at'      => 'required|date',
        ]);

        $classSection = ClassSection::find($request->class_section_id);
        $validated['grade_level_id'] = $classSection->grade_level_id;

        $enrollment->update($validated);

        return response()->json(['message' => __('enrollment.messages.success_update'), 'redirect' => route('enrollments.index')]);
    }

    public function destroy(StudentEnrollment $enrollment)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $enrollment->institution_id != $institutionId) {
            abort(403);
        }

        $enrollment->delete();
        return response()->json(['message' => __('enrollment.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        // Use 'delete' or 'deleteAny' depending on your seeder
        $this->authorize('deleteAny', StudentEnrollment::class); // Fallback to Policy if exists
        
        // OR explicit permission check if Policy is missing/incomplete
        if (!auth()->user()->can('enrollment.delete')) {
             abort(403);
        }

        $ids = $request->ids;
        
        if (!empty($ids)) {
            $institutionId = $this->getInstitutionId();
            $query = StudentEnrollment::whereIn('id', $ids);
            
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
            
            $query->delete();
            return response()->json(['success' => __('enrollment.messages.success_delete')]);
        }
        return response()->json(['error' => __('enrollment.something_went_wrong')]);
    }
}