<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Module;
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

        if ($request->ajax()) {
            $query = Role::withCount('users');
            $institutionId = $this->getInstitutionId();
            $isSuperAdmin = Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value);

            // 2. Multi-tenancy Filter
            if (!$isSuperAdmin) {
                $query->where(function($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)
                      ->orWhereNull('institution_id');
                });
                
                $query->where('name', '!=', RoleEnum::SUPER_ADMIN->value);
            }

            $query->latest(); 

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('users_count', function($row){
                    $count = $row->users_count ?? 0;
                    return '<span class="badge badge-primary">'.$count.'</span>';
                })
                ->addColumn('action', function($row) use ($isSuperAdmin, $institutionId) {
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    $isGlobal = is_null($row->institution_id);
                    $isOwnRole = $row->institution_id == $institutionId;
                    
                    $canEdit = auth()->user()->can('role.update') || $isSuperAdmin;
                    $canDelete = auth()->user()->can('role.delete') || $isSuperAdmin;

                    if($canEdit && ($isSuperAdmin || (!$isGlobal && $isOwnRole))) {
                        $btn .= '<a href="'.route('roles.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('roles.edit').'"><i class="fa fa-pencil"></i></a>';
                    }

                    $protectedNames = [RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value];
                    if($canDelete && !in_array($row->name, $protectedNames) && ($isSuperAdmin || (!$isGlobal && $isOwnRole))) {
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('roles.delete').'"><i class="fa fa-trash"></i></button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['users_count', 'action'])
                ->make(true);
        }

        return view('roles.index');
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
        $this->authorizeRoleAccess($role);
        $modules = $this->getAccessiblePermissions();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'modules', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeRoleAccess($role);

        if($role->name === RoleEnum::SUPER_ADMIN->value && !Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
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

        if (!Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            $this->verifyPermissionOwnership($request->permissions);
        }

        DB::transaction(function () use ($request, $role) {
            // Prevent renaming system roles
            if (!in_array($role->name, [RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value])) {
                $role->update(['name' => $request->name]);
            }
            $role->syncPermissions($request->input('permissions', []));
        });

        return response()->json(['message' => __('roles.messages.success_update'), 'redirect' => route('roles.index')]);
    }

    public function destroy(Role $role)
    {
        $this->authorizeRoleAccess($role);
        $protectedRoles = [RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value];
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
        
        // 1. Fetch All Modules
        $query = Module::with('permissions')->orderBy('name');
        $allModules = $query->get();

        // 2. Super Admin: Everything is active
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $allModules->map(function($module) {
                $module->is_subscribed = true;
                return $module;
            });
        }

        // 3. Fetch Subscription
        $institutionId = $this->getInstitutionId();
        $enabledModules = [];
        
        if ($institutionId) {
            $setting = InstitutionSetting::where('institution_id', $institutionId)
                ->where('key', 'enabled_modules')
                ->first();
            $enabledModules = $setting ? json_decode($setting->value, true) : [];
        }

        // 4. Map Subscription Status
        $coreModules = ['dashboard', 'profile', 'roles', 'permissions', 'settings', 'users'];

        return $allModules->map(function($module) use ($enabledModules, $coreModules) {
            $slug = Str::slug($module->name, '_');
            
            // A. Core Check
            if (in_array($slug, $coreModules)) {
                $module->is_subscribed = true;
            } 
            // B. Direct Match Check (Granular)
            elseif (in_array($slug, $enabledModules)) {
                $module->is_subscribed = true;
            } 
            // C. Parent Group Match Check (Legacy Compatibility)
            else {
                $parentGroup = $this->getParentGroup($slug);
                // If the PARENT group (e.g. 'academics') is enabled, then this child module is enabled
                if ($parentGroup && in_array($parentGroup, $enabledModules)) {
                    $module->is_subscribed = true;
                } else {
                    $module->is_subscribed = false;
                }
            }
            return $module;
        });
    }

    /**
     * Maps granular modules to legacy subscription packages.
     */
    private function getParentGroup($slug)
    {
        $groups = [
            'academics' => [
                'academic_sessions', 'grade_levels', 'class_sections', 'subjects', 
                'timetables', 'enrollments', 'student_promotion', 'students', 'student_attendance'
            ],
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