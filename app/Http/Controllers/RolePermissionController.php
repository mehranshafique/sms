<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use App\Enums\RoleEnum;

class RolePermissionController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('roles.role_permissions_page_title'));
    }

    public function edit(Role $role)
    {
        // Scope Check: Can't edit roles from other institutes
        $this->checkInstitution($role);

        $modules = Module::with('permissions')->get();
        $rolePermissions = $role->permissions()->pluck('name')->toArray();

        return view('roles.assign_permissions', compact('role', 'modules', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $user = Auth::user();

        $this->checkInstitution($role);

        if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value) && !$user->can('role.update')) {
            abort(403, __('roles.messages.unauthorized_action'));
        }

        // 1. Prevent updating own role permissions (unless Super Admin)
        if (!$user->hasRole('Super Admin') && $user->hasRole($role->name)) {
            return back()->with('error', __('roles.cannot_update_own_role'));
        }

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if($role->name === 'Super Admin') {
             // Optional safety: Super Admin always keeps all perms
             return redirect()->route('roles.index')->with('error', 'Cannot modify Super Admin permissions.');
        }

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', __('roles.messages.permission_assigned_successfully'));
    }
}