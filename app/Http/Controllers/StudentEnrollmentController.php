<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use App\Models\Student;
use App\Models\ClassSection;
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StudentEnrollmentController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(StudentEnrollment::class, 'student_enrollment');
        $this->setPageTitle(__('enrollment.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id;
        
        // Fetch active session based on user's institute or request
        $querySession = AcademicSession::query();
        if($institutionId) {
            $querySession->where('institution_id', $institutionId);
        }
        $currentSession = $querySession->where('is_current', true)->first();

        if ($request->ajax()) {
            // FIX: Select specific columns to avoid ID collision if joins happen
            $data = StudentEnrollment::with(['student', 'classSection', 'gradeLevel'])
                ->select('student_enrollments.*');

            // FIX: Qualify column name to avoid ambiguity (student_enrollments.institution_id)
            if ($institutionId) {
                $data->where('student_enrollments.institution_id', $institutionId);
            }

            // Filter by Session (Default to current if active)
            // FIX: Qualify academic_session_id as well
            if ($currentSession) {
                $data->where('student_enrollments.academic_session_id', $currentSession->id);
            }

            if ($request->has('class_section_id') && $request->class_section_id) {
                $data->where('student_enrollments.class_section_id', $request->class_section_id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    if(auth()->user()->can('delete', $row)){
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
                    return $row->student->admission_number ?? 'N/A';
                })
                ->addColumn('class', function($row){
                    return $row->classSection->name ?? 'N/A';
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
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('enrollments.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        // Dropdowns for Filter
        $classSectionsQuery = ClassSection::query();
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        $classSections = $classSectionsQuery->pluck('name', 'id');
        
        $sessionName = $currentSession ? $currentSession->name : __('enrollment.no_active_session');

        return view('enrollments.index', compact('classSections', 'sessionName'));
    }

    public function create()
    {
        $institutionId = Auth::user()->institute_id;
        
        // Classes
        $classesQuery = ClassSection::with(['gradeLevel', 'institution']);
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        $classes = $classesQuery->get()->mapWithKeys(function($item) use ($institutionId){
            $label = $item->name . ' (' . $item->gradeLevel->name . ')';
            if (!$institutionId && $item->institution) {
                $label .= ' - ' . $item->institution->code;
            }
            return [$item->id => $label];
        });

        // Current Session Check
        $querySession = AcademicSession::where('is_current', true);
        if($institutionId) {
            $querySession->where('institution_id', $institutionId);
        }
        $currentSession = $querySession->first();
        
        $sessionId = $currentSession ? $currentSession->id : 0;

        // Students (Not yet enrolled in this session)
        $studentsQuery = Student::whereDoesntHave('enrollments', function($q) use ($sessionId) {
            $q->where('academic_session_id', $sessionId);
        });
        
        if ($institutionId) {
            $studentsQuery->where('institution_id', $institutionId);
        } else {
            $studentsQuery->with('institution');
        }

        $students = $studentsQuery->select('id', 'first_name', 'last_name', 'admission_number', 'institution_id')
            ->get()
            ->mapWithKeys(function($item) use ($institutionId){
                $label = $item->full_name . ' (' . $item->admission_number . ')';
                if (!$institutionId && $item->institution) {
                    $label .= ' - ' . $item->institution->code;
                }
                return [$item->id => $label];
            });

        return view('enrollments.create', compact('classes', 'students'));
    }

    public function store(Request $request)
    {
        $userInstituteId = Auth::user()->institute_id;
        
        // 1. Determine Target Institution (From Student or Class)
        $classSection = ClassSection::with('gradeLevel')->findOrFail($request->class_section_id);
        $targetInstituteId = $classSection->institution_id;

        // 2. Get Current Session for THAT Institution
        $currentSession = AcademicSession::where('institution_id', $targetInstituteId)
            ->where('is_current', true)
            ->first();

        if(!$currentSession) {
            return response()->json(['message' => __('enrollment.no_active_session_error')], 422);
        }

        $validated = $request->validate([
            'student_id'       => [
                'required', 
                'exists:students,id',
                // Rule: Student unique per session
                Rule::unique('student_enrollments')->where(function ($query) use ($currentSession) {
                    return $query->where('academic_session_id', $currentSession->id);
                })
            ],
            'class_section_id' => 'required|exists:class_sections,id',
            'roll_number'      => [
                'nullable', 'string', 'max:20',
                // Rule: Roll number unique per section per session
                Rule::unique('student_enrollments')
                    ->where('academic_session_id', $currentSession->id)
                    ->where('class_section_id', $request->class_section_id)
            ],
            'status'           => 'required|in:active,promoted,detained,left',
            'enrolled_at'      => 'required|date',
        ]);

        $validated['institution_id'] = $targetInstituteId;
        $validated['academic_session_id'] = $currentSession->id;
        $validated['grade_level_id'] = $classSection->grade_level_id;

        StudentEnrollment::create($validated);

        return response()->json(['message' => __('enrollment.messages.success_create'), 'redirect' => route('enrollments.index')]);
    }

    public function edit(StudentEnrollment $enrollment)
    {
        $institutionId = Auth::user()->institute_id;
        
        $classesQuery = ClassSection::with('gradeLevel');
        if ($institutionId) {
            $classesQuery->where('institution_id', $institutionId);
        }
        $classes = $classesQuery->get()->mapWithKeys(function($item){
            return [$item->id => $item->name . ' (' . $item->gradeLevel->name . ')'];
        });

        $students = [$enrollment->student_id => $enrollment->student->full_name];

        return view('enrollments.edit', compact('enrollment', 'classes', 'students'));
    }

    public function update(Request $request, StudentEnrollment $enrollment)
    {
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
        $enrollment->delete();
        return response()->json(['message' => __('enrollment.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', StudentEnrollment::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            StudentEnrollment::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('enrollment.messages.success_delete')]);
        }
        return response()->json(['error' => __('enrollment.something_went_wrong')]);
    }
}