<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class RolePermissionController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':role.update')->only(['edit', 'update']);
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
        $this->checkInstitution($role);

        $user = Auth::user();
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