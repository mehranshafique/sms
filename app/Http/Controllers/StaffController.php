<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use App\Models\Institution;
use App\Models\Campus;
use App\Models\Role;
use App\Services\NotificationService;
use App\Services\RoleAssignmentService;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StaffController extends BaseController
{
    protected $notificationService;
    protected RoleAssignmentService $roleAssignment;

    public function __construct(NotificationService $notificationService, RoleAssignmentService $roleAssignment)
    {
        $this->authorizeResource(Staff::class, 'staff');
        $this->setPageTitle(__('staff.page_title'));
        $this->notificationService = $notificationService;
        $this->roleAssignment = $roleAssignment;
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $query = Staff::with(['user', 'institution', 'campus'])->select('staff.*');

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }

            return DataTables::of($query)
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
                ->addColumn('details', function($row){
                    $img = $row->user->profile_picture ? asset('storage/' . $row->user->profile_picture) : null;
                    $name = $row->user->name;
                    $email = $row->user->email;
                    $initial = strtoupper(substr($name, 0, 1));
                    
                    $avatarHtml = $img 
                        ? '<img src="'.$img.'" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;" alt="">'
                        : '<div class="head-officer-icon bgl-primary text-primary position-relative me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold;">'.$initial.'</div>';

                    return '<div class="d-flex align-items-center">
                                '.$avatarHtml.'
                                <div>
                                    <h6 class="fs-16 font-w600 mb-0"><a href="'.route('staff.show', $row->id).'" class="text-black">'.$name.'</a></h6>
                                    <span class="fs-13 text-muted">'.$email.'</span>
                                    <small class="d-block text-muted">'.$row->employee_id.'</small>
                                </div>
                            </div>';
                })
                ->addColumn('role', function($row){
                    return $row->user->roles->pluck('name')->join(', ');
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'active' => 'badge-success',
                        'on_leave' => 'badge-warning',
                        'resigned' => 'badge-secondary',
                        'terminated' => 'badge-danger'
                    ];
                    return '<span class="badge '.($badges[$row->status] ?? 'badge-light').'">'.ucfirst(str_replace('_', ' ', $row->status)).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    if(auth()->user()->can('view', $row)){
                        $btn .= '<a href="'.route('staff.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1" title="View"><i class="fa fa-eye"></i></a>';
                    }
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('staff.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="Edit"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="Delete"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $keyword = strtolower($request->search['value']);
                        $query->where(function($q) use ($keyword) {
                            $q->where('staff.employee_id', 'LIKE', "%{$keyword}%")
                              ->orWhere('staff.designation', 'LIKE', "%{$keyword}%")
                              ->orWhere('staff.department', 'LIKE', "%{$keyword}%")
                              ->orWhere('staff.nfc_uid', 'LIKE', "%{$keyword}%")
                              ->orWhere('staff.rfid_uid', 'LIKE', "%{$keyword}%")
                              ->orWhereHas('user', function($uq) use ($keyword) {
                                  $uq->where('name', 'LIKE', "%{$keyword}%")
                                     ->orWhere('email', 'LIKE', "%{$keyword}%")
                                     ->orWhere('phone', 'LIKE', "%{$keyword}%");
                              });
                        });
                    }
                })
                ->rawColumns(['checkbox', 'details', 'role', 'status', 'action'])
                ->make(true);
        }

        return view('staff.index');
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        $institutions = [];
        if ($institutionId) {
            $institutions = Institution::where('id', $institutionId)->pluck('name', 'id');
        } elseif (Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        }
        
        $campusesQuery = Campus::query();
        if ($institutionId) {
            $campusesQuery->where('institution_id', $institutionId);
        }
        $campuses = $campusesQuery->pluck('name', 'id');
        
        $rolesQuery = Role::where('name', '!=', RoleEnum::SUPER_ADMIN->value);

        $rolesQuery->whereNotNull('institution_id');

        if ($institutionId) {
            $rolesQuery->where('institution_id', $institutionId);
        }

        $roles = $rolesQuery->get();
        
        return view('staff.create', compact('institutions', 'campuses', 'roles', 'institutionId'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(function ($query) use ($institutionId) {
                    $query->where('name', '!=', RoleEnum::SUPER_ADMIN->value)
                          ->whereNotNull('institution_id');

                    if ($institutionId) {
                        $query->where('institution_id', $institutionId);
                    }
                }),
            ],
            'phone' => 'nullable|string',
            'profile_picture' => 'nullable|image|max:2048',
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'designation' => 'nullable|string',
            'joining_date' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
            'nfc_uid' => 'nullable|string|max:100',
            'rfid_uid' => 'nullable|string|max:100',
        ]);
       
        DB::transaction(function () use ($request, $institutionId) {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ];

            if ($request->hasFile('profile_picture')) {
                $userData['profile_picture'] = $request->file('profile_picture')->store('profile-photos', 'public');
            }

            $user = User::create($userData);
            $user->forceFill([
                'user_type' => UserType::STAFF->value,
                'institute_id' => $institutionId,
                'is_active' => true,
            ])->save();
            
            $assignedRole = $this->roleAssignment->assign($user, (int) $request->role_id, (int) $institutionId);

            $empId = $request->employee_id ?? 'EMP-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
            
            $user->update([
                'shortcode' => $empId,
                'username'  => $empId
            ]);

            Staff::create([
                'user_id' => $user->id,
                'institution_id' => $institutionId,
                'campus_id' => $request->campus_id,
                'employee_id' => $empId,
                'designation' => $request->designation,
                'department' => $request->department,
                'joining_date' => $request->joining_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'status' => 'active',
                'nfc_uid' => $request->nfc_uid,
                'rfid_uid' => $request->rfid_uid,
            ]);

            $user->refresh();
            $this->notificationService->sendUserCredentials($user, $request->password, $assignedRole->name);
        });

        return response()->json(['redirect' => route('staff.index'), 'message' => __('staff.messages.success_create')]);
    }

    public function show(Staff $staff)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $staff->institution_id != $institutionId) abort(403);

        $staff->load(['user', 'institution', 'campus']);
        return view('staff.show', compact('staff'));
    }

    public function edit(Staff $staff)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $staff->institution_id != $institutionId) abort(403);

        $staff->load('user');
        
        $institutions = Institution::where('id', $staff->institution_id)->pluck('name', 'id');
        $campuses = Campus::where('institution_id', $staff->institution_id)->pluck('name', 'id');
        
        $rolesQuery = Role::where('name', '!=', RoleEnum::SUPER_ADMIN->value);
        $rolesQuery->whereNotNull('institution_id');

        $instId = $staff->institution_id;
        if ($instId) {
            $rolesQuery->where('institution_id', $instId);
        }

        $roles = $rolesQuery->get();
        
        return view('staff.edit', compact('staff', 'institutions', 'campuses', 'roles', 'institutionId'));
    }

    public function update(Request $request, Staff $staff)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $staff->institution_id != $institutionId) abort(403);

        $user = $staff->user;
        $targetInstitutionId = $staff->institution_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(function ($query) use ($targetInstitutionId) {
                    $query->where('name', '!=', RoleEnum::SUPER_ADMIN->value)
                          ->whereNotNull('institution_id');

                    if ($targetInstitutionId) {
                        $query->where('institution_id', $targetInstitutionId);
                    }
                }),
            ],
            'profile_picture' => 'nullable|image|max:2048',
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'nfc_uid' => 'nullable|string|max:100',
            'rfid_uid' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request, $staff, $user, $institutionId, $targetInstitutionId) {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'mobile_number' => $request->phone, 
            ];

            if ($request->password) {
                $userData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $userData['profile_picture'] = $request->file('profile_picture')->store('profile-photos', 'public');
            }

            if ($request->employee_id && $request->employee_id !== $user->shortcode) {
                $userData['shortcode'] = $request->employee_id;
                $userData['username'] = $request->employee_id;
            }

            $user->update($userData);

            // School Admin may assign/change any institution role for school staff.
            // Prevent an admin from demoting themselves away from School Admin.
            $pendingRole = $this->roleAssignment->resolve((int) $request->role_id, (int) $targetInstitutionId);
            if (
                (int) $user->id === (int) Auth::id()
                && Auth::user()->hasRole(RoleEnum::SCHOOL_ADMIN->value)
                && $pendingRole->name !== RoleEnum::SCHOOL_ADMIN->value
            ) {
                abort(422, __('roles.cannot_demote_self'));
            }

            $assignedRoles = $this->roleAssignment->sync(
                $user,
                [(int) $request->role_id],
                (int) $targetInstitutionId
            );
            $assignedRoleName = $assignedRoles->first()?->name;

            $staff->update([
                'institution_id' => $institutionId ?? $request->institution_id,
                'campus_id' => $request->campus_id,
                'employee_id' => $request->employee_id,
                'designation' => $request->designation,
                'department' => $request->department,
                'joining_date' => $request->joining_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'status' => $request->status ?? $staff->status,
                'nfc_uid' => $request->nfc_uid,
                'rfid_uid' => $request->rfid_uid,
            ]);

            if($request->password) {
                $this->notificationService->sendUserCredentials($user, $request->password, $assignedRoleName);
            }
        });

        return response()->json(['redirect' => route('staff.index'), 'message' => __('staff.messages.success_update')]);
    }

    public function destroy(Staff $staff)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $staff->institution_id != $institutionId) abort(403);

        $user = $staff->user;
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }
        $staff->delete();
        $user->delete();
        return response()->json(['message' => __('staff.messages.success_delete')]);
    }
}