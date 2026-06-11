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
            // FIX: Using the correct table name 'parents' instead of 'student_parents'
            $query = Student::with(['classSection.gradeLevel', 'parent', 'user'])
                ->leftJoin('parents', 'students.parent_id', '=', 'parents.id')
                ->select('students.*');

            if ($institutionId) {
                $query->where('students.institution_id', $institutionId);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('details', function($row) {
                    $img = $row->student_photo ? asset('storage/' . $row->student_photo) : null;
                    
                    $nameForInitial = trim($row->first_name) ?: 'S';
                    $initial = mb_strtoupper(mb_substr($nameForInitial, 0, 1, 'UTF-8'));
                    
                    $avatarHtml = $img 
                        ? '<img src="'.$img.'" class="rounded-circle me-3" width="50" height="50" alt="" style="object-fit:cover;">' 
                        : '<div class="head-officer-icon bgl-primary text-primary position-relative me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold; font-size: 18px;">'.$initial.'</div>';
                    
                    $fName = mb_convert_encoding($row->first_name, 'UTF-8', 'UTF-8');
                    $lName = mb_convert_encoding($row->last_name, 'UTF-8', 'UTF-8');
                    $fullName = $fName . ' ' . $lName;
                    
                    return '<div class="d-flex align-items-center">
                                '.$avatarHtml.'
                                <div>
                                    <h6 class="mb-0 fw-bold"><a href="'.route('students.show', $row->id).'" class="text-black">'.$fullName.'</a></h6>
                                    <small class="text-muted">'.__('student.admission_no'). ': ' . ($row->admission_number ?? '-').'</small>
                                </div>
                            </div>';
                })
                ->addColumn('contact', function($row) {
                    return '<div class="small">
                                <div><i class="fa fa-phone text-primary me-1"></i> '.($row->mobile_number ?? '-').'</div>
                                <div><i class="fa fa-envelope text-primary me-1"></i> '.($row->email ?? '-').'</div>
                            </div>';
                })
                ->addColumn('parent_info', function($row) {
                    if (!$row->parent) return '<span class="text-muted">-</span>';
                    $pName = $row->parent->father_name ?? $row->parent->mother_name ?? $row->parent->guardian_name ?? 'N/A';
                    $pPhone = $row->parent->father_phone ?? $row->parent->mother_phone ?? $row->parent->guardian_phone ?? 'N/A';
                    return '<div class="small">
                                <div class="fw-bold">'.$pName.'</div>
                                <div><i class="fa fa-phone text-muted me-1"></i> '.$pPhone.'</div>
                            </div>';
                })
                ->addColumn('class', function($row) {
                    $grade = $row->classSection->gradeLevel->name ?? '';
                    $section = $row->classSection->name ?? '';
                    return trim($grade . ' ' . $section) ?: '-';
                })
                ->addColumn('status', function($row) {
                    $status = $row->status ?? 'active';
                    $badges = [
                        'active' => 'badge-success',
                        'inactive' => 'badge-secondary',
                        'suspended' => 'badge-danger',
                        'transferred' => 'badge-info',
                        'graduated' => 'badge-primary',
                    ];
                    $class = $badges[$status] ?? 'badge-light';
                    return '<span class="badge badge-sm '.$class.'">'.ucfirst($status).'</span>';
                })
                ->addColumn('action', function($row) {
                    $show = route('students.show', $row->id);
                    $edit = route('students.edit', $row->id);
                    $delete = route('students.destroy', $row->id);
                    
                    return '
                    <div class="d-flex">
                        <a href="'.$show.'" class="btn btn-info shadow btn-xs sharp me-1" title="View"><i class="fa fa-eye"></i></a>
                        <a href="'.$edit.'" class="btn btn-primary shadow btn-xs sharp me-1" title="Edit"><i class="fa fa-pencil"></i></a>
                        <button class="btn btn-danger shadow btn-xs sharp delete-btn" data-url="'.$delete.'" title="Delete"><i class="fa fa-trash"></i></button>
                    </div>';
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $keyword = strtolower($request->search['value']);
                        $query->where(function($q) use ($keyword) {
                            $q->where('students.first_name', 'LIKE', "%{$keyword}%")
                              ->orWhere('students.last_name', 'LIKE', "%{$keyword}%")
                              ->orWhereRaw("LOWER(CONCAT(students.first_name, ' ', students.last_name)) LIKE ?", ["%{$keyword}%"])
                              ->orWhere('students.admission_number', 'LIKE', "%{$keyword}%")
                              ->orWhere('students.mobile_number', 'LIKE', "%{$keyword}%")
                              ->orWhere('students.email', 'LIKE', "%{$keyword}%")
                              ->orWhere('students.rfid_uid', 'LIKE', "%{$keyword}%")
                              ->orWhere('students.nfc_tag_uid', 'LIKE', "%{$keyword}%")
                              ->orWhereHas('parent', function($pq) use ($keyword) {
                                  $pq->where('father_name', 'LIKE', "%{$keyword}%")
                                     ->orWhere('mother_name', 'LIKE', "%{$keyword}%")
                                     ->orWhere('guardian_name', 'LIKE', "%{$keyword}%")
                                     ->orWhere('father_phone', 'LIKE', "%{$keyword}%")
                                     ->orWhere('mother_phone', 'LIKE', "%{$keyword}%")
                                     ->orWhere('guardian_phone', 'LIKE', "%{$keyword}%");
                              })
                              ->orWhereHas('classSection', function($cq) use ($keyword) {
                                  $cq->where('name', 'LIKE', "%{$keyword}%")
                                     ->orWhereHas('gradeLevel', function($gq) use ($keyword) {
                                         $gq->where('name', 'LIKE', "%{$keyword}%");
                                     });
                              });
                        });
                    }
                })
                ->rawColumns(['details', 'contact', 'parent_info', 'status', 'action'])
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
            'email' => 'nullable|email|unique:users,email', 
            'password' => 'nullable|string|min:6', 
            'status' => 'required|string|in:active,inactive,suspended,transferred,graduated', 
            'national_id' => 'nullable|string|max:50',
            'origin_province' => 'nullable|string|max:100',
            'rfid_uid' => 'nullable|string|max:100', 
        ]);

        if ($request->filled('email') && $request->filled('guardian_email') && $request->email === $request->guardian_email) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => ['email' => ['The Student and Guardian cannot use the exact same email address.']]
            ], 422);
        }

        $parentFields = [
            'father_name', 'father_phone', 'father_occupation',
            'mother_name', 'mother_phone', 'mother_occupation',
            'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_email'
        ];
        
        $data = $request->except(array_merge(
            $parentFields, 
            ['_token', '_method', 'discount_amount', 'discount_type', 'scholarship_reason', 'primary_guardian', 'password']
        ));

        // Clean text fields
        $textFields = ['first_name', 'last_name', 'post_name', 'place_of_birth', 'origin_province', 'avenue', 'country', 'state', 'city', 'religion'];
        foreach ($textFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = mb_convert_case($data[$field], MB_CASE_TITLE, "UTF-8");
            }
        }
        
        if($request->filled('mobile_number')) {
            $data['mobile_number'] = $request->mobile_number;
        }

        try {
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

                // 2. Parent User Account
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
                        
                        $primaryPhone = $request->guardian_phone ?? $request->father_phone ?? $request->mother_phone;
                        if($primaryPhone && $primaryPhone !== $existingUser->phone) {
                            $existingUser->update(['phone' => $primaryPhone]);
                        }

                    } else {
                        $parentPlainPassword = 'Parent' . rand(1000, 9999) . '!';
                        $phone = $request->guardian_phone ?? $request->father_phone ?? $request->mother_phone;
                        $name = $request->guardian_name ?? $request->father_name ?? $request->mother_name ?? 'Parent';
                        
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
                            'shortcode' => $parentShortcode, 
                            'username' => $parentShortcode,  
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
                    $parent->update([
                        'father_phone' => $request->father_phone,
                        'mother_phone' => $request->mother_phone,
                        'guardian_phone' => $request->guardian_phone,
                        'guardian_relation' => $request->primary_guardian, 
                    ]);
                }

                // 4. Generate Admission Number
                $admissionNumber = IdGeneratorService::generateStudentId($institution, $currentSession);
                $data['institution_id'] = $institutionId;
                $data['parent_id'] = $parent->id; 
                $data['admission_number'] = $admissionNumber;

                if ($request->hasFile('student_photo')) {
                    $data['student_photo'] = $request->file('student_photo')->store('students', 'public');
                }

                // 5. Create Student USER Account (Handle Custom Password)
                $studentEmail = $request->email;
                $studentPlainPassword = $request->filled('password') ? $request->password : 'Student123!';
                
                if (empty($studentEmail)) {
                    $cleanAcronym = Str::slug($institution->acronym ?? 'school');
                    $studentEmail = str_replace(['/', ' ', '-'], '', $admissionNumber) . '@' . $cleanAcronym . '.com';
                }

                $studentUser = User::create([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $studentEmail,
                    'password' => Hash::make($studentPlainPassword),
                    'phone' => $request->mobile_number,
                    'user_type' => UserType::STUDENT->value,
                    'institute_id' => $institutionId,
                    'shortcode' => $admissionNumber, 
                    'username' => $admissionNumber,  
                    'is_active' => ($request->status === 'active'),
                ]);

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
                
                if ($sectionId && $request->status === 'active') {
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
                $this->notificationService->sendUserCredentials($studentUser, $studentPlainPassword, RoleEnum::STUDENT->value);
                
                if ($parentUserObj && $parentPlainPassword) {
                    $this->notificationService->sendUserCredentials($parentUserObj, $parentPlainPassword, RoleEnum::GUARDIAN->value);
                }
            });

            return response()->json(['redirect' => route('students.index'), 'message' => __('student.messages.success_create')]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error("Student Store DB Error: " . $e->getMessage());
            $errorCode = $e->errorInfo[1] ?? 0;
            if ($errorCode == 1062) {
                return response()->json(['message' => __('student.error_duplicate', ['default' => 'Duplicate entry detected! The Email, Phone Number, or Admission Number is already linked to another account.'])], 422);
            }
            return response()->json(['message' => __('student.error_database', ['default' => 'A database error occurred while saving. Please review your entries and try again.'])], 500);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Student Store Exception: " . $e->getMessage());
            return response()->json(['message' => __('student.error_occurred', ['default' => 'An unexpected error occurred: ']) . $e->getMessage()], 500);
        }
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
        
        $request->validate([
            'first_name' => 'required|string|max:100', 
            'last_name' => 'required|string|max:100',
            'password' => 'nullable|string|min:6', 
            'status' => 'required|string|in:active,inactive,suspended,transferred,graduated',
            'national_id' => 'nullable|string|max:50',
            'origin_province' => 'nullable|string|max:100',
            'rfid_uid' => 'nullable|string|max:100', 
        ]);

        $parentFields = ['father_name', 'father_phone', 'mother_name', 'mother_phone', 'guardian_name', 'guardian_phone', 'guardian_email'];
        $parentData = $request->only($parentFields);
        
        $studentData = $request->except(array_merge(
            $parentFields, 
            ['_token', '_method', 'discount_amount', 'discount_type', 'scholarship_reason', 'admission_number', 'institution_id', 'email', 'primary_guardian', 'password']
        ));
        
        if($request->filled('mobile_number')) {
            $studentData['mobile_number'] = $request->mobile_number;
        }

        try {
            DB::transaction(function () use ($student, $studentData, $parentData, $request) {
                if ($request->hasFile('student_photo')) {
                    if ($student->student_photo) Storage::disk('public')->delete($student->student_photo);
                    $studentData['student_photo'] = $request->file('student_photo')->store('students', 'public');
                }
                
                // 1. Update Student Profile
                $student->update($studentData);

                // 2. Sync Student User
                if ($student->user_id) {
                    $userUpdate = [];
                    if($request->filled('first_name')) $userUpdate['name'] = $request->first_name . ' ' . $request->last_name;
                    
                    if ($request->filled('mobile_number')) {
                        $userUpdate['phone'] = $request->mobile_number;
                    }

                    if ($request->filled('email') && $request->email !== $student->user->email) {
                        if (!User::where('email', $request->email)->where('id', '!=', $student->user_id)->exists()) {
                            $userUpdate['email'] = $request->email;
                            $student->update(['email' => $request->email]);
                        }
                    }
                    
                    $userUpdate['is_active'] = ($request->status === 'active');

                    // If custom password is provided, securely hash it and update
                    if ($request->filled('password')) {
                        $userUpdate['password'] = Hash::make($request->password);
                    }
                    
                    User::where('id', $student->user_id)->update($userUpdate);
                }

                // 3. Update Parent Info
                if ($student->parent) {
                    $student->parent->update($parentData);
                    
                    $parentUser = null;
                    $plainPassword = null;
                    $isNewParent = false;

                    if (!empty($parentData['guardian_email']) && !$student->parent->user_id) {
                        $existingUser = User::where('email', $parentData['guardian_email'])->first();
                        if ($existingUser) {
                            $student->parent->update(['user_id' => $existingUser->id]);
                            $parentUser = $existingUser;
                        } else {
                            $plainPassword = 'Parent' . rand(1000, 9999) . '!';
                            $isNewParent = true;
                            
                            $parentShortcode = 'PAR-' . $student->institution_id . '-' . rand(10000, 99999);
                            while(User::where('shortcode', $parentShortcode)->exists()) {
                                 $parentShortcode = 'PAR-' . $student->institution_id . '-' . rand(10000, 99999);
                            }

                            $phone = $parentData['guardian_phone'] ?? $parentData['father_phone'] ?? $parentData['mother_phone'];

                            $newUser = User::create([
                                'name' => $parentData['guardian_name'] ?? $parentData['father_name'] ?? 'Parent',
                                'email' => $parentData['guardian_email'],
                                'password' => Hash::make($plainPassword),
                                'phone' => $phone, 
                                'user_type' => UserType::GUARDIAN->value,
                                'institute_id' => $student->institution_id,
                                'shortcode' => $parentShortcode,
                                'username' => $parentShortcode,
                                'is_active' => true,
                            ]);
                            $newUser->assignRole('Guardian');
                            $student->parent->update(['user_id' => $newUser->id]);
                            $parentUser = $newUser;
                        }
                    } elseif ($student->parent->user_id) {
                         $phone = $parentData['guardian_phone'] ?? $parentData['father_phone'] ?? $parentData['mother_phone'];
                         if($phone) {
                             User::where('id', $student->parent->user_id)->update(['phone' => $phone]);
                         }
                    }

                    if ($request->filled('primary_guardian')) {
                        $student->parent->update(['guardian_relation' => $request->primary_guardian]);
                    }
                    
                    if ($isNewParent && $parentUser && $plainPassword) {
                        $this->notificationService->sendUserCredentials($parentUser, $plainPassword, RoleEnum::GUARDIAN->value);
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

        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error("Student Update DB Error: " . $e->getMessage());
            $errorCode = $e->errorInfo[1] ?? 0;
            if ($errorCode == 1062) {
                return response()->json(['message' => __('student.error_duplicate', ['default' => 'Duplicate entry detected! The Email or Phone Number you entered is already linked to another account.'])], 422);
            }
            return response()->json(['message' => __('student.error_database', ['default' => 'A database error occurred while updating. Please review your entries.'])], 500);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Student Update Exception: " . $e->getMessage());
            return response()->json(['message' => __('student.error_occurred', ['default' => 'An unexpected error occurred: ']) . $e->getMessage()], 500);
        }
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