<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Institution;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\AcademicSession; // Added
use App\Services\IdGeneratorService; // Added
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Student::class, 'student');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Student::with(['institution', 'campus', 'gradeLevel'])->select('students.*');

            if ($request->has('grade_level_id') && $request->grade_level_id) {
                $data->where('grade_level_id', $request->grade_level_id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                <label class="form-check-label"></label>
                            </div>';
                })
                ->addColumn('details', function($row){
                    $img = $row->student_photo ? asset('storage/' . $row->student_photo) : null;
                    $initial = strtoupper(substr($row->first_name, 0, 1));
                    
                    $avatarHtml = $img 
                        ? '<img src="'.$img.'" class="rounded-circle me-3" width="50" height="50" alt="">'
                        : '<div class="head-officer-icon bgl-primary text-primary position-relative me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold;">'.$initial.'</div>';

                    return '<div class="d-flex align-items-center">
                                '.$avatarHtml.'
                                <div>
                                    <h6 class="fs-16 font-w600 mb-0"><a href="'.route('students.show', $row->id).'" class="text-black">'.$row->full_name.'</a></h6>
                                    <span class="fs-13 text-muted">ID: '.$row->admission_number.'</span>
                                </div>
                            </div>';
                })
                ->addColumn('parent_info', function($row){
                    return '<div><i class="fa fa-user me-1"></i> '.$row->father_name.'</div>
                            <div class="text-muted"><i class="fa fa-phone me-1"></i> '.$row->father_phone.'</div>';
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'active' => 'badge-success',
                        'transferred' => 'badge-warning',
                        'suspended' => 'badge-danger',
                        'graduated' => 'badge-info',
                        'inactive' => 'badge-secondary'
                    ];
                    return '<span class="badge '.($badges[$row->status] ?? 'badge-secondary').'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    $btn .= '<a href="'.route('students.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    $btn .= '<a href="'.route('students.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    $btn .= '<button class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'details', 'parent_info', 'status', 'action'])
                ->make(true);
        }

        return view('students.index');
    }

    public function create()
    {
        $institutes = Institution::pluck('name', 'id');
        $campuses = Campus::pluck('name', 'id');
        $gradeLevels = GradeLevel::pluck('name', 'id');
        
        return view('students.create', compact('institutes', 'campuses', 'gradeLevels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'admission_date' => 'required|date',
            'dob' => 'required|date',
            'gender' => 'required',
            'mobile_number' => 'nullable|numeric',
            'student_photo' => 'nullable|image|max:2048',
            'qr_code_token' => 'nullable|string|unique:students,qr_code_token',
            'nfc_tag_uid' => 'nullable|string|unique:students,nfc_tag_uid',
        ]);

        $data = $request->all();

        // 1. Get Institution
        $institution = Institution::findOrFail($request->institution_id);

        // 2. Get Current Active Academic Session for YY Logic
        $currentSession = AcademicSession::where('institution_id', $institution->id)
            ->where('is_current', true)
            ->first();

        if (!$currentSession) {
            throw ValidationException::withMessages(['institution_id' => 'No active academic session found for this institution. Cannot generate Student ID.']);
        }

        // 3. Generate Permanent ID
        $data['admission_number'] = IdGeneratorService::generateStudentId($institution, $currentSession);

        // 4. Handle Photo
        if ($request->hasFile('student_photo')) {
            $data['student_photo'] = $request->file('student_photo')->store('students', 'public');
        }

        // 5. Create Student
        Student::create($data);

        return response()->json(['redirect' => route('students.index'), 'message' => __('student.messages.success_create')]);
    }

    public function show(Student $student)
    {
        $student->load(['institution', 'campus', 'gradeLevel', 'enrollments.academicSession', 'enrollments.classSection']);
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $institutes = Institution::pluck('name', 'id');
        $campuses = Campus::pluck('name', 'id');
        $gradeLevels = GradeLevel::pluck('name', 'id');

        return view('students.edit', compact('student', 'institutes', 'campuses', 'gradeLevels'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'dob' => 'required|date',
            'student_photo' => 'nullable|image|max:2048',
            'qr_code_token' => 'nullable|string|unique:students,qr_code_token,' . $student->id,
            'nfc_tag_uid' => 'nullable|string|unique:students,nfc_tag_uid,' . $student->id,
        ]);

        $data = $request->all();

        // Prevent ID Change
        unset($data['admission_number']); 
        unset($data['institution_id']);

        if ($request->hasFile('student_photo')) {
            if ($student->student_photo) {
                Storage::disk('public')->delete($student->student_photo);
            }
            $data['student_photo'] = $request->file('student_photo')->store('students', 'public');
        }

        $student->update($data);

        return response()->json(['redirect' => route('students.index'), 'message' => __('student.messages.success_update')]);
    }

    public function destroy(Student $student)
    {
        if ($student->student_photo) {
            Storage::disk('public')->delete($student->student_photo);
        }
        $student->delete();
        return response()->json(['message' => __('student.messages.success_delete')]);
    }
}