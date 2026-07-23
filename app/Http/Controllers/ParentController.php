<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StudentParent;
use App\Models\Institution;
use App\Enums\UserType;
use App\Enums\RoleEnum;
use App\Services\IdGeneratorService;
use App\Services\RoleAssignmentService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ParentController extends BaseController
{
    protected RoleAssignmentService $roleAssignment;

    public function __construct(RoleAssignmentService $roleAssignment)
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':student_parent.view')->only(['index', 'show']);
        $this->middleware(PermissionMiddleware::class . ':student_parent.create')->only(['create', 'store', 'check']);
        $this->middleware(PermissionMiddleware::class . ':student_parent.update')->only(['edit', 'update']);
        $this->middleware(PermissionMiddleware::class . ':student_parent.delete')->only(['destroy', 'bulkDelete']);
        $this->setPageTitle(__('parent.page_title'));
        $this->roleAssignment = $roleAssignment;
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = StudentParent::with(['user', 'students'])
                ->select('parents.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function($row){
                    $name = $row->guardian_name ?? $row->father_name ?? $row->mother_name ?? 'N/A';
                    return '<a href="'.route('parents.show', $row->id).'" class="fw-bold text-primary">'.$name.'</a>';
                })
                ->addColumn('phones', function($row){
                    $phones = [];
                    if($row->father_phone) $phones[] = '<i class="fa fa-male me-1 text-muted"></i> '.$row->father_phone;
                    if($row->mother_phone) $phones[] = '<i class="fa fa-female me-1 text-muted"></i> '.$row->mother_phone;
                    if($row->guardian_phone) $phones[] = '<i class="fa fa-user me-1 text-muted"></i> '.$row->guardian_phone;
                    return implode('<br>', $phones);
                })
                ->addColumn('email', function($row){
                    return $row->guardian_email ?? $row->user->email ?? '-';
                })
                ->addColumn('wards', function($row){
                    return '<span class="badge badge-info">'.$row->students->count().' Students</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    $btn .= '<a href="'.route('parents.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('parents.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['name', 'phones', 'wards', 'action'])
                ->make(true);
        }

        return view('parents.index');
    }

    public function create()
    {
        return view('parents.create');
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'guardian_name' => 'nullable|required_without_all:father_name,mother_name|string|max:100',
            'guardian_phone' => 'nullable|required_without_all:father_phone,mother_phone|string|max:20',
            'guardian_email' => 'nullable|email|unique:users,email',
            'username' => 'nullable|string|max:50|unique:users,username',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $credentialsMessage = null;

        DB::transaction(function () use ($request, $institutionId, &$credentialsMessage) {
            $userResult = $this->createOrUpdateGuardianUser($request, null, $institutionId);
            $userId = $userResult['user']?->id;

            StudentParent::create([
                'institution_id' => $institutionId,
                'user_id' => $userId,
                'father_name' => $request->father_name,
                'father_phone' => $request->father_phone,
                'father_occupation' => $request->father_occupation,
                'mother_name' => $request->mother_name,
                'mother_phone' => $request->mother_phone,
                'mother_occupation' => $request->mother_occupation,
                'guardian_name' => $request->guardian_name,
                'guardian_phone' => $request->guardian_phone,
                'guardian_email' => $request->guardian_email,
                'guardian_relation' => $request->guardian_relation,
                'family_address' => $request->family_address,
            ]);

            if ($userResult['user']) {
                $credentialsMessage = __('parent.credentials_created', [
                    'id' => $userResult['user']->shortcode,
                    'username' => $userResult['user']->username,
                    'password' => $userResult['plain_password'],
                ]);
            }
        });

        $success = __('parent.success_create');
        if ($credentialsMessage) {
            $success .= ' ' . $credentialsMessage;
        }

        return $this->successResponse($success, route('parents.index'));
    }

    public function show(StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);
        
        $parent->load(['students.classSection.gradeLevel', 'user']);
        return view('parents.show', compact('parent'));
    }

    public function edit(StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);

        $parent->load('user');

        return view('parents.edit', compact('parent'));
    }

    public function update(Request $request, StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);

        $request->validate([
            'guardian_email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($parent->user_id),
            ],
            'username' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($parent->user_id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request, $parent, $institutionId) {
            $this->createOrUpdateGuardianUser($request, $parent, $institutionId);

            $parent->update($request->only([
                'father_name', 'father_phone', 'father_occupation',
                'mother_name', 'mother_phone', 'mother_occupation',
                'guardian_name', 'guardian_phone', 'guardian_email',
                'guardian_relation', 'family_address',
            ]));
        });

        return $this->successResponse(__('parent.success_update'), route('parents.index'));
    }

    public function destroy(StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);

        if ($parent->students()->count() > 0) {
            return response()->json(['message' => __('parent.cannot_delete')], 422);
        }

        if ($parent->user_id) {
            User::destroy($parent->user_id);
        }
        
        $parent->delete();

        return response()->json(['message' => __('parent.success_delete')]);
    }

    /**
     * Create or update the linked guardian user account.
     *
     * @return array{user: ?User, plain_password: ?string}
     */
    private function createOrUpdateGuardianUser(Request $request, ?StudentParent $parent, ?int $institutionId): array
    {
        $name = $request->guardian_name ?? $request->father_name ?? $request->mother_name ?? 'Parent';
        $phone = $request->guardian_phone ?? $request->father_phone ?? $request->mother_phone;
        $email = $request->guardian_email;

        $existingUser = $parent?->user_id ? User::find($parent->user_id) : null;

        if (!$existingUser && !$email && !$phone && !$name) {
            return ['user' => null, 'plain_password' => null];
        }

        $institution = $institutionId ? Institution::find($institutionId) : null;
        $plainPassword = $request->filled('password') ? $request->password : null;

        if ($existingUser) {
            $updates = [
                'name' => $name,
                'phone' => $phone,
            ];

            if ($email) {
                $updates['email'] = $email;
            }

            if ($request->filled('username')) {
                $updates['username'] = $request->username;
            }

            if ($plainPassword) {
                $updates['password'] = Hash::make($plainPassword);
            }

            $existingUser->update($updates);

            if (!$existingUser->shortcode && $institution) {
                $shortcode = IdGeneratorService::generateParentShortcode($institution, $existingUser->id);
                $existingUser->update([
                    'shortcode' => $shortcode,
                    'username' => $existingUser->username ?: $shortcode,
                ]);
            }

            return ['user' => $existingUser->fresh(), 'plain_password' => $plainPassword];
        }

        if (!$institution) {
            return ['user' => null, 'plain_password' => null];
        }

        $plainPassword = $plainPassword ?? ('Parent' . rand(1000, 9999) . '!');

        if (!$email) {
            $email = 'par.' . $institution->id . '.' . uniqid('', true) . '@parents.local';
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'phone' => $phone,
        ]);
        $user->forceFill([
            'user_type' => UserType::GUARDIAN->value,
            'institute_id' => $institutionId,
            'is_active' => true,
        ])->save();

        $shortcode = IdGeneratorService::generateParentShortcode($institution, $user->id);
        $username = $request->username ?: $shortcode;

        while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
            $username = $shortcode . '-' . rand(10, 99);
        }

        $user->update([
            'shortcode' => $shortcode,
            'username' => $username,
        ]);

        $this->roleAssignment->assign($user, RoleEnum::GUARDIAN->value, (int) $institutionId);

        if ($parent) {
            $parent->update(['user_id' => $user->id]);
        }

        return ['user' => $user->fresh(), 'plain_password' => $plainPassword];
    }

    public function check(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:6',
        ]);

        $phone = $request->phone;

        $studentParent = StudentParent::where(function($query) use ($phone) {
            $query->where('father_phone', $phone)
                  ->orWhere('mother_phone', $phone)
                  ->orWhere('guardian_phone', $phone);
        })->first();

        if ($studentParent) {
            $displayName = $studentParent->guardian_name ?? $studentParent->father_name ?? $studentParent->mother_name ?? 'Parent';
            
            if ($studentParent->father_phone == $phone && !empty($studentParent->father_name)) {
                $displayName = $studentParent->father_name;
            } elseif ($studentParent->mother_phone == $phone && !empty($studentParent->mother_name)) {
                $displayName = $studentParent->mother_name;
            } elseif ($studentParent->guardian_phone == $phone && !empty($studentParent->guardian_name)) {
                $displayName = $studentParent->guardian_name;
            }

            return response()->json([
                'exists' => true,
                'source' => 'parent_record',
                'id' => $studentParent->id,
                'parent_id' => $studentParent->id,
                'user_id' => $studentParent->user_id,
                'name' => $displayName,
                'email' => $studentParent->guardian_email,
                'father_name' => $studentParent->father_name,
                'father_phone' => $studentParent->father_phone,
                'mother_name' => $studentParent->mother_name,
                'mother_phone' => $studentParent->mother_phone,
                'guardian_name' => $studentParent->guardian_name,
                'guardian_email' => $studentParent->guardian_email,
                'guardian_phone' => $studentParent->guardian_phone,
                'guardian_relation' => $studentParent->guardian_relation,
                'matched_type' => $this->resolveMatchedGuardianType($studentParent, $phone),
            ]);
        }

        $userParent = User::where('phone', $phone)
            ->where('user_type', UserType::GUARDIAN->value)
            ->first();

        if ($userParent) {
            return response()->json([
                'exists' => true,
                'source' => 'user_account',
                'id' => $userParent->id,
                'parent_id' => null,
                'user_id' => $userParent->id,
                'name' => $userParent->name,
                'email' => $userParent->email,
                'guardian_name' => $userParent->name,
                'guardian_email' => $userParent->email,
                'guardian_phone' => $userParent->phone,
                'guardian_relation' => 'guardian',
                'matched_type' => 'guardian',
            ]);
        }

        return response()->json(['exists' => false]);
    }

    private function resolveMatchedGuardianType(StudentParent $parent, string $phone): ?string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';
        $matches = function (?string $stored) use ($normalized): bool {
            if (!$stored) {
                return false;
            }
            $storedNorm = preg_replace('/\D+/', '', $stored) ?? '';

            return $storedNorm !== '' && $storedNorm === $normalized;
        };

        if ($matches($parent->father_phone)) {
            return 'father';
        }
        if ($matches($parent->mother_phone)) {
            return 'mother';
        }
        if ($matches($parent->guardian_phone)) {
            return 'guardian';
        }

        return $parent->guardian_relation;
    }
}
