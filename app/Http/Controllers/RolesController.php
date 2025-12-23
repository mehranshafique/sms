<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RolesController extends BaseController
{
    public function __construct()
    {
        // FIX: Commented out to prevent auto-blocking Role ID 1
        // $this->authorizeResource(Role::class, 'role');
        $this->setPageTitle(__('roles.page_title'));
    }

    public function index(Request $request)
    {
        // Manual Auth Check
        if (!Auth::user()->can('role.viewAny') && !Auth::user()->hasRole('Super Admin')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        if ($request->ajax()) {
            // FIX: Removed select('*') to ensure withCount('users') works correctly
            $query = Role::withCount('users');

            // DIGITEX ARCHITECTURE: Hierarchy Check
            if (!Auth::user()->hasRole('Super Admin')) {
                // Head Officers cannot see 'Super Admin' role
                $query->where('name', '!=', 'Super Admin');
            }

            // Order by latest
            $query->latest(); 

            return \Yajra\DataTables\Facades\DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    // Prevent deleting core roles
                    $protectedRoles = ['Super Admin', 'Head Officer', 'Teacher', 'Student'];
                    if(!in_array($row->name, $protectedRoles) && auth()->user()->can('role.delete')){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('users_count', function($row){
                    // Ensure users_count is treated as an integer, default to 0 if null
                    $count = $row->users_count ?? 0;
                    return '<span class="badge badge-primary">'.$count.'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('role.update') || auth()->user()->hasRole('Super Admin')){
                        // Allow Super Admin to edit everything, others cannot edit Super Admin role
                        if($row->name !== 'Super Admin' || auth()->user()->hasRole('Super Admin')) {
                            $btn .= '<a href="'.route('roles.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('roles.edit').'">
                                        <i class="fa fa-pencil"></i>
                                    </a>';
                        }
                    }

                    $protectedRoles = ['Super Admin', 'Head Officer', 'Teacher', 'Student'];
                    if(auth()->user()->can('role.delete') && !in_array($row->name, $protectedRoles)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('roles.delete').'">
                                    <i class="fa fa-trash"></i>
                                </button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'users_count', 'action'])
                ->make(true);
        }

        $totalRoles = Role::count();
        $rolesWithUsers = Role::has('users')->count();
        
        return view('roles.index', compact('totalRoles', 'rolesWithUsers'));
    }

    public function create()
    {
        if (!Auth::user()->can('role.create')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        $modules = $this->getAccessiblePermissions();
        return view('roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('role.create')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $this->verifyPermissionOwnership($request->permissions);

        DB::transaction(function () use ($request) {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        return response()->json(['message' => __('roles.messages.success_create'), 'redirect' => route('roles.index')]);
    }

    public function edit(Role $role)
    {
        // FIX: Allow Super Admin to edit Role ID 1 explicitly
        if ($role->id == 1 && Auth::user()->hasRole('Super Admin')) {
            // Allowed
        } elseif (!Auth::user()->can('role.update')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        // Prevent non-super admins from editing Super Admin role
        if($role->name === 'Super Admin' && !Auth::user()->hasRole('Super Admin')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        $modules = $this->getAccessiblePermissions();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        
        return view('roles.edit', compact('role', 'modules', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        // FIX: Allow Super Admin to update Role ID 1
        if ($role->id == 1 && Auth::user()->hasRole('Super Admin')) {
            // Allowed
        } elseif (!Auth::user()->can('role.update')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        if($role->name === 'Super Admin' && $request->name !== 'Super Admin') {
             return response()->json(['message' => __('roles.cannot_edit_super_admin_name')], 403);
        }

        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $this->verifyPermissionOwnership($request->permissions);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]);
            }
        });

        return response()->json(['message' => __('roles.messages.success_update'), 'redirect' => route('roles.index')]);
    }

    public function destroy(Role $role)
    {
        if (!Auth::user()->can('role.delete')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        $protectedRoles = ['Super Admin', 'Head Officer', 'Teacher', 'Student'];
        if (in_array($role->name, $protectedRoles)) {
            return response()->json(['error' => __('roles.cannot_delete_system_role')], 403);
        }
        
        $role->delete();
        return response()->json(['message' => __('roles.messages.success_delete')]);
    }

    private function getAccessiblePermissions()
    {
        $user = Auth::user();
        $query = Module::with(['permissions' => function($q) use ($user) {
            if (!$user->hasRole('Super Admin')) {
                $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
                $q->whereIn('name', $userPermissions);
            }
        }]);

        $modules = $query->get()->filter(function($module) {
            return $module->permissions->isNotEmpty();
        });

        return $modules;
    }

    private function verifyPermissionOwnership($requestedPermissions)
    {
        if (empty($requestedPermissions)) return;
        if (Auth::user()->hasRole('Super Admin')) return;

        $userPermissions = Auth::user()->getAllPermissions()->pluck('name')->toArray();
        
        foreach ($requestedPermissions as $perm) {
            if (!in_array($perm, $userPermissions)) {
                abort(403, __('roles.messages.unauthorized_assignment', ['perm' => $perm]));
            }
        }
    }
}