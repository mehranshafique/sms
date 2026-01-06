<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\Institution;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Services\IdGeneratorService;
use App\Services\NotificationService;
use App\Enums\UserType;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

class StudentController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->authorizeResource(Student::class, 'student');
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = Student::with(['institution', 'campus', 'gradeLevel'])
                ->select('students.*')
                ->latest('students.created_at');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            if ($request->has('grade_level_id') && $request->grade_level_id) {
                $data->where('grade_level_id', $request->grade_level_id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3"><input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'"><label class="form-check-label"></label></div>';
                })
                ->addColumn('details', function($row){
                    $img = $row->student_photo ? asset('storage/' . $row->student_photo) : null;
                    $initial = strtoupper(substr($row->first_name, 0, 1));
                    $avatarHtml = $img ? '<img src="'.$img.'" class="rounded-circle me-3" width="50" height="50" alt="">' : '<div class="head-officer-icon bgl-primary text-primary position-relative me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold;">'.$initial.'</div>';
                    
                    return '<div class="d-flex align-items-center">'.$avatarHtml.'<div><h6 class="fs-16 font-w600 mb-0"><a href="'.route('students.show', $row->id).'" class="text-black">'.$row->full_name.'</a></h6><span class="fs-13 text-muted">'.__('student.id').': '.($row->admission_number ?? '-').'</span></div></div>';
                })
                ->addColumn('parent_info', function($row){
                    return '<div><i class="fa fa-user me-1"></i> '.$row->father_name.'</div><div class="text-muted"><i class="fa fa-phone me-1"></i> '.$row->father_phone.'</div>';
                })
                ->editColumn('status', function($row){
                    $badges = ['active' => 'badge-success', 'transferred' => 'badge-warning', 'suspended' => 'badge-danger', 'graduated' => 'badge-info', 'inactive' => 'badge-secondary'];
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
        $institutionId = $this->getInstitutionId();
        
        $institutes = [];
        $campuses = [];
        $gradeLevels = [];
        $currentSession = null;

        if ($institutionId) {
            $institutes = Institution::where('id', $institutionId)->pluck('name', 'id');
            $campuses = Campus::where('institution_id', $institutionId)->pluck('name', 'id');
            $gradeLevels = GradeLevel::where('institution_id', $institutionId)->pluck('name', 'id');
            $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        } elseif (Auth::user()->hasRole('Super Admin')) {
            $institutes = Institution::pluck('name', 'id');
        }
        
        return view('students.create', compact('institutes', 'campuses', 'gradeLevels', 'currentSession', 'institutionId'));
    }

    public function getSections(Request $request) 
    {
        $gradeId = $request->grade_id;
        $institutionId = $this->getInstitutionId();

        $grade = GradeLevel::find($gradeId);
        if (!$grade || ($institutionId && $grade->institution_id != $institutionId)) {
            return response()->json([]);
        }

        $sectionsQuery = ClassSection::with('gradeLevel')
            ->where('grade_level_id', $gradeId)
            ->where('is_active', true);
            
        if ($institutionId) {
            $sectionsQuery->where('institution_id', $institutionId);
        }
        
        $sections = $sectionsQuery->get()->mapWithKeys(function($item) {
             $gradeName = $item->gradeLevel->name ?? '';
             return [$item->id => $item->name . ($gradeName ? ' (' . $gradeName . ')' : '')];
        });
        
        return response()->json($sections);
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;

        $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'admission_date' => 'required|date',
            'dob' => 'required|date',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'class_section_id' => 'nullable|exists:class_sections,id',
            'email' => 'nullable|email|unique:users,email',
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'religion' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'avenue' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            // Discount Validation
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'required_with:discount_amount|in:fixed,percentage',
            'scholarship_reason' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['_token', '_method', 'discount_amount', 'discount_type', 'scholarship_reason']);

        // --- Capitalize Text Fields (Title Case) ---
        $textFields = [
            'first_name', 'last_name', 'post_name', 'place_of_birth', 
            'avenue', 'father_name', 'mother_name', 'country', 'state', 'city', 'religion'
        ];
        
        foreach ($textFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = ucwords(strtolower($data[$field]));
            }
        }

        DB::transaction(function () use ($request, $data, $institutionId) {
            $institution = Institution::findOrFail($institutionId);
            $currentSession = AcademicSession::where('institution_id', $institutionId)
                ->where('is_current', true)
                ->first();

            if (!$currentSession) {
                throw ValidationException::withMessages(['institution_id' => __('student.no_active_session')]);
            }

            $data['institution_id'] = $institutionId; 
            
            // 1. CREATE USER FIRST
            $user = null;
            if ($request->email) {
                $plainPassword = 'Student' . rand(1000, 9999) . '!';
                
                $user = User::create([
                    'name' => $data['first_name'] . ' ' . $data['last_name'],
                    'email' => $request->email,
                    'password' => Hash::make($plainPassword),
                    'user_type' => UserType::STUDENT->value,
                    'institute_id' => $institutionId,
                    'is_active' => true,
                ]);
                
                $user->assignRole('Student');
                $data['user_id'] = $user->id;
                
                // Send credentials later
                $this->notificationService->sendUserCredentials($user, $plainPassword, 'Student');
            }

            // 2. GENERATE ID
            $userId = $user ? $user->id : null;
            $data['admission_number'] = IdGeneratorService::generateStudentId($institution, $currentSession, $userId);

            if ($request->hasFile('student_photo')) {
                $data['student_photo'] = $request->file('student_photo')->store('students', 'public');
            }

            // 3. CREATE STUDENT
            $student = Student::create($data);

            $sectionId = $request->class_section_id;
            if (!$sectionId) {
                $section = ClassSection::where('grade_level_id', $request->grade_level_id)
                    ->where('institution_id', $institutionId)
                    ->first();
                $sectionId = $section ? $section->id : null;
            }

            // 4. CREATE ENROLLMENT WITH DISCOUNT
            if ($sectionId) {
                StudentEnrollment::create([
                    'institution_id' => $institutionId,
                    'academic_session_id' => $currentSession->id,
                    'student_id' => $student->id,
                    'grade_level_id' => $request->grade_level_id,
                    'class_section_id' => $sectionId,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    // Save Discount Details
                    'discount_amount' => $request->discount_amount ?? 0,
                    'discount_type' => $request->discount_type ?? 'fixed',
                    'scholarship_reason' => $request->scholarship_reason,
                ]);
            }

            $phone = $student->mobile_number ?? $student->father_phone;
            if ($phone) {
                $smsData = [
                    'StudentName' => $student->full_name,
                    'AdmissionNumber' => $student->admission_number,
                    'SchoolName' => $institution->name
                ];
                $this->notificationService->sendSmsEvent('student_admission', $phone, $smsData, $institutionId);
            }
        });

        return response()->json(['redirect' => route('students.index'), 'message' => __('student.messages.success_create')]);
    }

    public function edit(Student $student)
    {
        $institutionId = $this->getInstitutionId();

        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, __('student.unauthorized_access'));
        }

        $institutes = Institution::where('id', $student->institution_id)->pluck('name', 'id');
        $campuses = Campus::where('institution_id', $student->institution_id)->pluck('name', 'id');
        $gradeLevels = GradeLevel::where('institution_id', $student->institution_id)->pluck('name', 'id');

        return view('students.edit', compact('student', 'institutes', 'campuses', 'gradeLevels', 'institutionId'));
    }

    public function show(Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);
        
        $student->load(['institution', 'campus', 'gradeLevel', 'enrollments.academicSession', 'enrollments.classSection']);
        return view('students.show', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);
        
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'dob' => 'required|date',
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            // Discount Validation
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'required_with:discount_amount|in:fixed,percentage',
            'scholarship_reason' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['_token', '_method', 'discount_amount', 'discount_type', 'scholarship_reason']);
        unset($data['admission_number']); 
        unset($data['institution_id']); 

        // --- Capitalize Text Fields (Title Case) ---
        $textFields = [
            'first_name', 'last_name', 'post_name', 'place_of_birth', 
            'avenue', 'father_name', 'mother_name', 'country', 'state', 'city', 'religion'
        ];
        
        foreach ($textFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = ucwords(strtolower($data[$field]));
            }
        }

        if ($request->hasFile('student_photo')) {
            if ($student->student_photo) {
                Storage::disk('public')->delete($student->student_photo);
            }
            $data['student_photo'] = $request->file('student_photo')->store('students', 'public');
        }

        // DB Transaction to update Student AND Enrollment discount
        DB::transaction(function () use ($student, $data, $request) {
            $student->update($data);
            
            if($student->user_id) {
                $userLink = User::find($student->user_id);
                if($userLink) {
                    $userLink->update([
                        'name' => $data['first_name'] . ' ' . $data['last_name'],
                        'email' => $request->email ?? $userLink->email,
                    ]);
                }
            }

            // Update Current Enrollment Discount
            $enrollment = $student->enrollments()->latest()->first();
            if ($enrollment) {
                $enrollment->update([
                    'discount_amount' => $request->discount_amount ?? 0,
                    'discount_type' => $request->discount_type ?? 'fixed',
                    'scholarship_reason' => $request->scholarship_reason,
                ]);
            }
        });

        return response()->json(['redirect' => route('students.index'), 'message' => __('student.messages.success_update')]);
    }

    public function destroy(Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);
        
        try {
            DB::transaction(function () use ($student) {
                if ($student->student_photo) {
                    Storage::disk('public')->delete($student->student_photo);
                }
                if($student->user_id) {
                    User::destroy($student->user_id);
                }
                $student->delete();
            });
            
            return response()->json(['message' => __('student.messages.success_delete')]);

        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1451) {
                return response()->json([
                    'message' => __('student.messages.cannot_delete_linked_data') ?? 'Cannot delete student because they are linked to other records (e.g. Grades, Attendance).'
                ], 422);
            }
            
            return response()->json(['message' => __('student.messages.error_occurred') ?? 'An error occurred while deleting the student.'], 500);
        }
    }
}