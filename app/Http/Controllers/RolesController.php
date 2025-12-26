<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Module;
use App\Models\Institution; 
use App\Models\InstitutionSetting;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class RolesController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('roles.page_title'));
    }

    public function index(Request $request)
    {
        // 1. Auth Check
        if (!Auth::user()->can('role.viewAny') && !Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        $isSuperAdmin = Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value);
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            // Join to get code and name efficiently, avoiding N+1
            $query = Role::leftJoin('institutions', 'roles.institution_id', '=', 'institutions.id')
                ->select([
                    'roles.*', 
                    'institutions.code as institution_code', 
                    'institutions.name as institution_name'
                ]);

            // 2. User Count Logic (Scoped to Context)
            $query->withCount(['users' => function ($q) use ($institutionId) {
                if ($institutionId) {
                    $q->where('users.institute_id', $institutionId);
                }
            }]);

            // 3. Multi-tenancy Filter for Rows
            if (!$isSuperAdmin) {
                // Head Officer sees: Their own roles AND Global roles
                $query->where(function($q) use ($institutionId) {
                    $q->where('roles.institution_id', $institutionId)
                      ->orWhereNull('roles.institution_id');
                });
                
                $query->where('roles.name', '!=', RoleEnum::SUPER_ADMIN->value);
            } else {
                // Super Admin Logic:
                // If a specific institution is selected in session, show THAT institution's roles + Global roles.
                // If Global View (institutionId is null), show ALL roles.
                if ($institutionId) {
                     $query->where(function($q) use ($institutionId) {
                        $q->where('roles.institution_id', $institutionId)
                          ->orWhereNull('roles.institution_id');
                    });
                }
            }

            $query->latest('roles.created_at'); 

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function($row) use ($isSuperAdmin) {
                    // Show role name with institution code for Super Admin
                    if ($isSuperAdmin && $row->institution_code) {
                        return $row->name . ' (' . $row->institution_code . ')';
                    }
                    return $row->name;
                })
                ->addColumn('institution', function($row) {
                    return $row->institution_name ?? '<span class="badge badge-secondary">System Global</span>';
                })
                ->editColumn('users_count', function($row){
                    $count = $row->users_count ?? 0;
                    return '<span class="badge badge-primary">'.$count.'</span>';
                })
                ->addColumn('action', function($row) use ($isSuperAdmin, $institutionId) {
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    $isGlobal = is_null($row->institution_id);
                    $isOwnRole = $row->institution_id == $institutionId;
                    
                    $canEdit = auth()->user()->can('role.update') || $isSuperAdmin;
                    $canDelete = auth()->user()->can('role.delete') || $isSuperAdmin;

                    // --- EDIT LOGIC UPDATE ---
                    $isHeadOfficer = $row->name === RoleEnum::HEAD_OFFICER->value;
                    $isSuperAdminRole = $row->name === RoleEnum::SUPER_ADMIN->value;

                    $allowEdit = false;
                    
                    if ($isSuperAdmin) {
                        // Super Admin can edit Head Officer, Teacher, Student, etc.
                        // Only prevent editing 'Super Admin' role itself to be safe
                        if (!$isSuperAdminRole) {
                            $allowEdit = true;
                        }
                    } else {
                        // Normal User: Can edit own roles, but NOT Head Officer
                        if ($canEdit && (!$isGlobal && $isOwnRole) && !$isHeadOfficer) {
                            $allowEdit = true;
                        }
                    }

                    if ($allowEdit) {
                        $btn .= '<a href="'.route('roles.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('roles.edit').'"><i class="fa fa-pencil"></i></a>';
                    }

                    // --- DELETE LOGIC ---
                    $protectedNames = [
                        RoleEnum::SUPER_ADMIN->value, 
                        RoleEnum::HEAD_OFFICER->value,
                        RoleEnum::TEACHER->value,
                        RoleEnum::STUDENT->value
                    ];

                    $allowDelete = false;
                    if ($canDelete && !in_array($row->name, $protectedNames)) {
                        if ($isSuperAdmin || (!$isGlobal && $isOwnRole)) {
                            $allowDelete = true;
                        }
                    }

                    if ($allowDelete) {
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('roles.delete').'"><i class="fa fa-trash"></i></button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['institution', 'users_count', 'action']) 
                ->make(true);
        }

        return view('roles.index', compact('isSuperAdmin'));
    }

    public function create()
    {
        $modules = $this->getAccessiblePermissions();
        return view('roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles')->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId);
                })
            ],
            'permissions' => 'nullable|array',
        ]);

        if (!Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            $this->verifyPermissionOwnership($request->permissions);
        }

        DB::transaction(function () use ($request, $institutionId) {
            $role = Role::create([
                'name' => $request->name, 
                'guard_name' => 'web',
                'institution_id' => $institutionId
            ]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        return response()->json(['message' => __('roles.messages.success_create'), 'redirect' => route('roles.index')]);
    }

    public function edit(Role $role)
    {
        $isSuperAdmin = Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value);

        // Allow Super Admin to edit 'Head Officer'
        if (!$isSuperAdmin && $role->name === RoleEnum::HEAD_OFFICER->value) {
            abort(403, __('roles.messages.cannot_edit_system'));
        }

        $this->authorizeRoleAccess($role);
        $modules = $this->getAccessiblePermissions();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'modules', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeRoleAccess($role);
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);

        // 1. Prevent user from updating their own role (except Super Admin)
        if (!$isSuperAdmin && $user->hasRole($role->name)) {
            return response()->json(['message' => __('roles.cannot_update_own_role')], 403);
        }

        // 2. Head Officer Check: Only Super Admin can edit
        if (!$isSuperAdmin && $role->name === RoleEnum::HEAD_OFFICER->value) {
            return response()->json(['message' => __('roles.messages.cannot_edit_system')], 403);
        }

        // 3. System Roles Check: Only Super Admin can edit global roles
        if($role->institution_id === null && !$isSuperAdmin) {
             return response()->json(['message' => __('roles.messages.cannot_edit_system')], 403);
        }

        $institutionId = $this->getInstitutionId();

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles')->ignore($role->id)->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId);
                })
            ],
            'permissions' => 'nullable|array',
        ]);

        if (!$isSuperAdmin) {
            $this->verifyPermissionOwnership($request->permissions);
        }

        DB::transaction(function () use ($request, $role, $isSuperAdmin) {
            $systemProtectedNames = [
                RoleEnum::SUPER_ADMIN->value, 
                RoleEnum::HEAD_OFFICER->value,
                RoleEnum::TEACHER->value,
                RoleEnum::STUDENT->value
            ];

            // Only update name if not protected (Even Super Admin shouldn't rename system roles to avoid logic breaks)
            if (!in_array($role->name, $systemProtectedNames)) {
                $role->update(['name' => $request->name]);
            }
            
            $role->syncPermissions($request->input('permissions', []));
        });

        return response()->json(['message' => __('roles.messages.success_update'), 'redirect' => route('roles.index')]);
    }

    public function destroy(Role $role)
    {
        $this->authorizeRoleAccess($role);
        
        $protectedRoles = [
            RoleEnum::SUPER_ADMIN->value, 
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::TEACHER->value,
            RoleEnum::STUDENT->value
        ];

        if (in_array($role->name, $protectedRoles)) {
            return response()->json(['message' => __('roles.messages.cannot_delete_system')], 403);
        }
        
        if ($role->users()->count() > 0) {
            return response()->json(['message' => __('roles.messages.role_has_users')], 403);
        }
        
        $role->delete();
        return response()->json(['message' => __('roles.messages.success_delete')]);
    }

    // --- HELPER METHODS ---

    private function getAccessiblePermissions()
    {
        $user = Auth::user();
        $query = Module::with('permissions')->orderBy('name');
        $allModules = $query->get();

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $allModules->map(function($module) {
                $module->is_subscribed = true;
                return $module;
            });
        }

        $institutionId = $this->getInstitutionId();
        $enabledModules = [];
        
        if ($institutionId) {
            $setting = InstitutionSetting::where('institution_id', $institutionId)
                ->where('key', 'enabled_modules')
                ->first();
            $enabledModules = $setting ? json_decode($setting->value, true) : [];
        }

        $coreModules = ['dashboard', 'profile', 'roles', 'permissions', 'settings', 'users'];

        return $allModules->map(function($module) use ($enabledModules, $coreModules) {
            $slug = Str::slug($module->name, '_');
            
            if (in_array($slug, $coreModules)) {
                $module->is_subscribed = true;
            } 
            elseif (in_array($slug, $enabledModules)) {
                $module->is_subscribed = true;
            } 
            else {
                $parentGroup = $this->getParentGroup($slug);
                if ($parentGroup && in_array($parentGroup, $enabledModules)) {
                    $module->is_subscribed = true;
                } else {
                    $module->is_subscribed = false;
                }
            }
            return $module;
        });
    }

    private function getParentGroup($slug)
    {
        $groups = [
            'academics' => ['academic_sessions', 'grade_levels', 'class_sections', 'subjects', 'timetables', 'enrollments', 'student_promotion', 'students', 'student_attendance'],
            'hr' => ['staff'],
            'examinations' => ['exams', 'exam_marks'],
            'finance' => ['fee_structures', 'fee_types', 'invoices', 'payments'],
            'library' => ['books', 'book_issues'],
            'transport' => ['routes', 'vehicles'],
            'inventory' => ['items', 'suppliers'],
        ];

        foreach ($groups as $groupKey => $modules) {
            if (in_array($slug, $modules)) {
                return $groupKey;
            }
        }

        return null;
    }

    private function verifyPermissionOwnership($requestedPermissions)
    {
        if (empty($requestedPermissions)) return;
        $modules = $this->getAccessiblePermissions();
        $allowed = [];
        foreach($modules as $m) {
            if($m->is_subscribed) {
                foreach($m->permissions as $p) {
                    $allowed[] = $p->name;
                }
            }
        }
        foreach ($requestedPermissions as $perm) {
            if (!in_array($perm, $allowed)) {
                abort(403, __('roles.messages.unauthorized_assignment', ['perm' => $perm]));
            }
        }
    }

    private function authorizeRoleAccess($role)
    {
        if(Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) return true;
        if($role->institution_id && $role->institution_id != $this->getInstitutionId()) {
            abort(403, __('roles.messages.unauthorized_access'));
        }
        if(is_null($role->institution_id)) {
            abort(403, __('roles.messages.cannot_edit_system'));
        }
    }
}