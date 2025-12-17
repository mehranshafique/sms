<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Module; // Ensure you have this model
use Illuminate\Support\Facades\DB;

class RolesController extends BaseController
{
    public function __construct()
    {
        // Enforce Resource Policy
        $this->authorizeResource(Role::class, 'role');
        $this->setPageTitle(__('roles.page_title'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Role::withCount('users')->select('*');
            return \Yajra\DataTables\Facades\DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    // Only allow deleting non-super-admin roles or custom logic
                    if(auth()->user()->can('delete', $row) && $row->name !== 'Super Admin'){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('users_count', function($row){
                    return '<span class="badge badge-primary">'.$row->users_count.'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('roles.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('roles.edit').'">
                                    <i class="fa fa-pencil"></i>
                                </a>';
                    }

                    if(auth()->user()->can('delete', $row) && $row->name !== 'Super Admin'){
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

        // Stats Logic
        $totalRoles = Role::count();
        $rolesWithUsers = Role::has('users')->count();
        // You can add more specific stats here
        
        return view('roles.index', compact('totalRoles', 'rolesWithUsers'));
    }

    public function create()
    {
        // Fetch all modules with their permissions
        $modules = Module::with('permissions')->get();
        return view('roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name', // Validate permission names
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create(['name' => $request->name]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        return response()->json(['message' => __('roles.messages.success_create'), 'redirect' => route('roles.index')]);
    }

    public function edit(Role $role)
    {
        // Fetch all modules with their permissions
        $modules = Module::with('permissions')->get();
        // Get permissions assigned to this role
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        
        return view('roles.edit', compact('role', 'modules', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        // Prevent editing Super Admin name if restricted logic applies
        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                // If no permissions sent (checkboxes unchecked), revoke all
                $role->syncPermissions([]);
            }
        });

        return response()->json(['message' => __('roles.messages.success_update'), 'redirect' => route('roles.index')]);
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return response()->json(['error' => __('roles.cannot_delete_super_admin')], 403);
        }
        
        $role->delete();
        return response()->json(['message' => __('roles.messages.success_delete')]);
    }
}