<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentParent;
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
use App\Enums\RoleEnum; 
use Spatie\Permission\Models\Role; 
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            // OPTIMIZED QUERY: Join 'parents' to allow sorting/searching by parent name
            $query = Student::leftJoin('parents', 'students.parent_id', '=', 'parents.id')
                ->select([
                    'students.id',
                    'students.institution_id',
                    'students.first_name',
                    'students.last_name',
                    'students.admission_number',
                    'students.student_photo',
                    'students.status',
                    'students.created_at',
                    'students.grade_level_id', 
                    // Select Parent fields with aliases to avoid Accessor collision (getFatherNameAttribute)
                    'parents.father_name as parent_father_name',
                    'parents.mother_name as parent_mother_name',
                    'parents.guardian_name as parent_guardian_name',
                    'parents.father_phone as parent_father_phone',
                    'parents.mother_phone as parent_mother_phone',
                    'parents.guardian_phone as parent_guardian_phone'
                ]);

            if ($institutionId) {
                $query->where('students.institution_id', $institutionId);
            }

            if ($request->has('grade_level_id') && $request->grade_level_id) {
                $query->where('students.grade_level_id', $request->grade_level_id);
            }

            $query->orderBy('students.created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                // --- FIX MALFORMED UTF-8 CHARACTERS ---
                ->editColumn('first_name', fn($row) => mb_convert_encoding($row->first_name, 'UTF-8', 'UTF-8'))
                ->editColumn('last_name', fn($row) => mb_convert_encoding($row->last_name, 'UTF-8', 'UTF-8'))
                ->editColumn('parent_father_name', fn($row) => mb_convert_encoding($row->parent_father_name ?? '', 'UTF-8', 'UTF-8'))
                ->editColumn('parent_mother_name', fn($row) => mb_convert_encoding($row->parent_mother_name ?? '', 'UTF-8', 'UTF-8'))
                ->editColumn('parent_guardian_name', fn($row) => mb_convert_encoding($row->parent_guardian_name ?? '', 'UTF-8', 'UTF-8'))
                
                ->addColumn('checkbox', function($row){
                    return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3"><input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'"><label class="form-check-label"></label></div>';
                })
                ->addColumn('details', function($row){
                    $img = $row->student_photo ? asset('storage/' . $row->student_photo) : null;
                    
                    $nameForInitial = trim($row->first_name) ?: 'S';
                    $initial = mb_strtoupper(mb_substr($nameForInitial, 0, 1, 'UTF-8'));
                    
                    $avatarHtml = $img 
                        ? '<img src="'.$img.'" class="rounded-circle me-3" width="50" height="50" alt="">' 
                        : '<div class="head-officer-icon bgl-primary text-primary position-relative me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold; font-size: 18px;">'.$initial.'</div>';
                    
                    $fName = mb_convert_encoding($row->first_name, 'UTF-8', 'UTF-8');
                    $lName = mb_convert_encoding($row->last_name, 'UTF-8', 'UTF-8');
                    $fullName = $fName . ' ' . $lName;
                    
                    return '<div class="d-flex align-items-center">'.$avatarHtml.'<div><h6 class="fs-16 font-w600 mb-0"><a href="'.route('students.show', $row->id).'" class="text-black">'.$fullName.'</a></h6><span class="fs-13 text-muted">'.__('student.id').': '.($row->admission_number ?? '-').'</span></div></div>';
                })
                ->addColumn('parent_info', function($row){
                    $name = $row->parent_father_name ?? $row->parent_mother_name ?? $row->parent_guardian_name ?? 'N/A';
                    $phone = $row->parent_father_phone ?? $row->parent_mother_phone ?? $row->parent_guardian_phone ?? 'N/A';
                    
                    $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
                    
                    return '<div><i class="fa fa-user me-1"></i> '.$name.'</div><div class="text-muted"><i class="fa fa-phone me-1"></i> '.$phone.'</div>';
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
        
        $sectionsQuery = ClassSection::where('is_active', true);
        
        if($gradeId) {
            $sectionsQuery->where('grade_level_id', $gradeId);
        }
        
        if ($institutionId) {
            $sectionsQuery->where('institution_id', $institutionId);
        }
        
        $data = $sectionsQuery->get()->mapWithKeys(function($item) {
            return [$item->id => mb_convert_encoding($item->name, 'UTF-8', 'UTF-8')];
        });

        return response()->json($data);
    }

    public function checkParent(Request $request)
    {
        $value = $request->query('value'); 
        $institutionId = $this->getInstitutionId();

        if (!$value) return response()->json(['exists' => false]);

        $query = StudentParent::where('institution_id', $institutionId);

        if ($request->has('email') || strpos($value, '@') !== false) {
            $query->where('guardian_email', $value);
        } else {
            $query->where(function($q) use ($value) {
                $q->where('father_phone', $value)
                  ->orWhere('mother_phone', $value)
                  ->orWhere('guardian_phone', $value);
            });
        }

        $parent = $query->first();

        if ($parent) {
            $displayName = $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? 'Parent';
            return response()->json([
                'exists' => true,
                'name' => mb_convert_encoding($displayName, 'UTF-8', 'UTF-8'),
                'father_name' => $parent->father_name,
                'mother_name' => $parent->mother_name,
                'guardian_name' => $parent->guardian_name,
                'guardian_email' => $parent->guardian_email,
                'parent_id' => $parent->id
            ]);
        }

        return response()->json(['exists' => false]);
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
            'primary_guardian' => 'required|in:father,mother,guardian',
            'guardian_email' => 'nullable|email',
            'email' => 'nullable|email|unique:users,email', // Check student email uniqueness
        ]);

        $parentFields = [
            'father_name', 'father_phone', 'father_occupation',
            'mother_name', 'mother_phone', 'mother_occupation',
            'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_email'
        ];
        
        $data = $request->except(array_merge(
            $parentFields, 
            ['_token', '_method', 'discount_amount', 'discount_type', 'scholarship_reason', 'primary_guardian']
        ));

        // Clean text fields
        $textFields = ['first_name', 'last_name', 'post_name', 'place_of_birth', 'avenue', 'country', 'state', 'city', 'religion'];
        foreach ($textFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = mb_convert_case($data[$field], MB_CASE_TITLE, "UTF-8");
            }
        }

        DB::transaction(function () use ($request, $data, $institutionId) {
            $institution = Institution::findOrFail($institutionId);
            $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->firstOrFail();

            // 1. Find Parent
            $phones = array_filter([$request->father_phone, $request->mother_phone, $request->guardian_phone]);
            $email = $request->guardian_email;

            $parent = StudentParent::where('institution_id', $institutionId)
                ->where(function($q) use ($phones, $email) {
                    if(!empty($phones)) {
                        $q->where(function($sub) use ($phones) {
                            $sub->whereIn('father_phone', $phones)
                                ->orWhereIn('mother_phone', $phones)
                                ->orWhereIn('guardian_phone', $phones);
                        });
                    }
                    if($email) {
                        $q->orWhere('guardian_email', $email);
                    }
                })->first();

            // 2. Parent User Account & Shortcode Generation
            $parentUserId = $parent ? $parent->user_id : null;
            $parentPlainPassword = null;
            $parentUserObj = null;

            if ($email) {
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $parentUserId = $existingUser->id;
                    $parentUserObj = $existingUser;
                    if(!$existingUser->hasRole('Guardian')) {
                        $existingUser->assignRole('Guardian');
                    }
                } else {
                    $parentPlainPassword = 'Parent' . rand(1000, 9999) . '!';
                    $phone = $request->guardian_phone ?? $request->father_phone ?? $request->mother_phone;
                    $name = $request->guardian_name ?? $request->father_name ?? $request->mother_name ?? 'Parent';
                    
                    // Generate Unique Shortcode for Parent: PAR-{InstID}-{Random}
                    $parentShortcode = 'PAR-' . $institutionId . '-' . rand(10000, 99999);
                    while(User::where('shortcode', $parentShortcode)->exists()) {
                         $parentShortcode = 'PAR-' . $institutionId . '-' . rand(10000, 99999);
                    }

                    $newUser = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($parentPlainPassword),
                        'phone' => $phone,
                        'user_type' => UserType::GUARDIAN->value,
                        'institute_id' => $institutionId,
                        'shortcode' => $parentShortcode, // Added Shortcode
                        'username' => $parentShortcode,  // Added Username match
                        'is_active' => true,
                    ]);
                    $newUser->assignRole('Guardian'); 
                    $parentUserId = $newUser->id;
                    $parentUserObj = $newUser;
                }
            }

            // 3. Create/Link Parent Record
            if (!$parent) {
                $parent = StudentParent::create([
                    'institution_id' => $institutionId,
                    'user_id' => $parentUserId,
                    'father_name' => $request->father_name,
                    'father_phone' => $request->father_phone,
                    'father_occupation' => $request->father_occupation,
                    'mother_name' => $request->mother_name,
                    'mother_phone' => $request->mother_phone,
                    'mother_occupation' => $request->mother_occupation,
                    'guardian_name' => $request->guardian_name,
                    'guardian_relation' => $request->primary_guardian,
                    'guardian_phone' => $request->guardian_phone,
                    'guardian_email' => $request->guardian_email,
                    'family_address' => $request->avenue, 
                ]);
            } else {
                if (!$parent->user_id && $parentUserId) {
                    $parent->update(['user_id' => $parentUserId]);
                }
            }

            // 4. Generate Admission Number
            $admissionNumber = IdGeneratorService::generateStudentId($institution, $currentSession);
            $data['institution_id'] = $institutionId;
            $data['parent_id'] = $parent->id; 
            $data['admission_number'] = $admissionNumber;
            $data['primary_guardian'] = $request->primary_guardian; 

            if ($request->hasFile('student_photo')) {
                $data['student_photo'] = $request->file('student_photo')->store('students', 'public');
            }

            // 5. Create Student USER Account (Updated Logic)
            $studentEmail = $request->email;
            $studentPlainPassword = 'Student123!';
            
            // If email is not provided, generate a unique dummy email: ID@acronym.school
            if (empty($studentEmail)) {
                $cleanAcronym = Str::slug($institution->acronym ?? 'school');
                $studentEmail = str_replace(['/', ' ', '-'], '', $admissionNumber) . '@' . $cleanAcronym . '.com';
            }

            $studentUser = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $studentEmail,
                'password' => Hash::make($studentPlainPassword), // Default password
                'user_type' => UserType::STUDENT->value,
                'institute_id' => $institutionId,
                'shortcode' => $admissionNumber, // Set Shortcode = Admission Number
                'username' => $admissionNumber,  // Set Username = Admission Number
                'is_active' => true,
            ]);

            // Assign Student Role
            $studentRole = Role::where('name', RoleEnum::STUDENT->value)
                               ->where('institution_id', $institutionId)
                               ->first();
            if ($studentRole) {
                $studentUser->assignRole($studentRole);
            }

            $data['user_id'] = $studentUser->id;
            
            // 6. Create Student Profile
            $student = Student::create($data);

            // 7. Enroll
            $sectionId = $request->class_section_id;
            if (!$sectionId) {
                $section = ClassSection::where('grade_level_id', $request->grade_level_id)
                    ->where('institution_id', $institutionId)
                    ->first();
                $sectionId = $section ? $section->id : null;
            }
            
            if ($sectionId) {
                StudentEnrollment::create([
                    'institution_id' => $institutionId,
                    'academic_session_id' => $currentSession->id,
                    'student_id' => $student->id,
                    'grade_level_id' => $request->grade_level_id,
                    'class_section_id' => $sectionId,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'discount_amount' => $request->discount_amount ?? 0,
                    'discount_type' => $request->discount_type ?? 'fixed',
                    'scholarship_reason' => $request->scholarship_reason,
                ]);
            }

            // 8. SEND NOTIFICATIONS
            // Send to Student
            $this->notificationService->sendUserCredentials($studentUser, $studentPlainPassword, RoleEnum::STUDENT->value);
            
            // Send to Parent (if new user created)
            if ($parentUserObj && $parentPlainPassword) {
                $this->notificationService->sendUserCredentials($parentUserObj, $parentPlainPassword, RoleEnum::GUARDIAN->value);
            }
        });

        return response()->json(['redirect' => route('students.index'), 'message' => __('student.messages.success_create')]);
    }

    public function edit(Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);

        $institutes = Institution::where('id', $student->institution_id)->pluck('name', 'id');
        $campuses = Campus::where('institution_id', $student->institution_id)->pluck('name', 'id');
        $gradeLevels = GradeLevel::where('institution_id', $student->institution_id)->pluck('name', 'id');

        return view('students.edit', compact('student', 'institutes', 'campuses', 'gradeLevels', 'institutionId'));
    }

    public function show(Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);
        
        $student->load(['institution', 'campus', 'gradeLevel', 'parent', 'enrollments.academicSession', 'enrollments.classSection', 'user']);
        return view('students.show', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);
        
        $request->validate(['first_name' => 'required', 'last_name' => 'required']);

        $parentFields = ['father_name', 'father_phone', 'mother_name', 'mother_phone', 'guardian_name', 'guardian_phone', 'guardian_email'];
        $parentData = $request->only($parentFields);
        
        $studentData = $request->except(array_merge(
            $parentFields, 
            ['_token', '_method', 'discount_amount', 'discount_type', 'scholarship_reason', 'admission_number', 'institution_id', 'email']
        ));

        // Note: We avoid updating admission_number as it's the unique ID (shortcode)

        DB::transaction(function () use ($student, $studentData, $parentData, $request) {
            if ($request->hasFile('student_photo')) {
                if ($student->student_photo) Storage::disk('public')->delete($student->student_photo);
                $studentData['student_photo'] = $request->file('student_photo')->store('students', 'public');
            }
            
            // 1. Update Student Profile
            $student->update($studentData);

            // 2. Sync Student User (Name & Email if changed in request)
            if ($student->user_id && ($request->filled('email') || $request->filled('first_name'))) {
                $userUpdate = [];
                $userUpdate['name'] = $request->first_name . ' ' . $request->last_name;
                
                // Only update email if provided and different
                if ($request->filled('email') && $request->email !== $student->user->email) {
                    // Check uniqueness
                    if (!User::where('email', $request->email)->where('id', '!=', $student->user_id)->exists()) {
                        $userUpdate['email'] = $request->email;
                        // Also update profile email field for consistency
                        $student->update(['email' => $request->email]);
                    }
                }
                
                User::where('id', $student->user_id)->update($userUpdate);
            }

            // 3. Update Parent Info
            if ($student->parent) {
                $student->parent->update($parentData);
                
                if (!empty($parentData['guardian_email']) && !$student->parent->user_id) {
                    $existingUser = User::where('email', $parentData['guardian_email'])->first();
                    if ($existingUser) {
                        $student->parent->update(['user_id' => $existingUser->id]);
                    } else {
                        $plainPassword = 'Parent' . rand(1000, 9999) . '!';
                        
                        // Generate Parent Shortcode
                        $parentShortcode = 'PAR-' . $student->institution_id . '-' . rand(10000, 99999);
                        while(User::where('shortcode', $parentShortcode)->exists()) {
                             $parentShortcode = 'PAR-' . $student->institution_id . '-' . rand(10000, 99999);
                        }

                        $newUser = User::create([
                            'name' => $parentData['guardian_name'] ?? $parentData['father_name'] ?? 'Parent',
                            'email' => $parentData['guardian_email'],
                            'password' => Hash::make($plainPassword),
                            'user_type' => UserType::GUARDIAN->value,
                            'institute_id' => $student->institution_id,
                            'shortcode' => $parentShortcode, // Added
                            'username' => $parentShortcode,  // Added
                            'is_active' => true,
                        ]);
                        $newUser->assignRole('Guardian');
                        $student->parent->update(['user_id' => $newUser->id]);
                        
                        // NOTIFY NEW PARENT
                        $this->notificationService->sendUserCredentials($newUser, $plainPassword, RoleEnum::GUARDIAN->value);
                    }
                }
            }

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
                if ($student->student_photo) Storage::disk('public')->delete($student->student_photo);
                if($student->user_id) User::destroy($student->user_id);
                $student->delete();
            });
            return response()->json(['message' => __('student.messages.success_delete')]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error deleting student.'], 500);
        }
    }
}