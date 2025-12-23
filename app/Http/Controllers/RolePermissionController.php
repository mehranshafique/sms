<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Module;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('roles.role_permissions_page_title'));
    }

    /**
     * Show the form for editing permissions of a specific role.
     * Note: This is also covered in RolesController@edit.
     */
    public function edit(Role $role)
    {
        $modules = Module::with('permissions')->get();
        $rolePermissions = $role->permissions()->pluck('name')->toArray(); // Changed to pluck 'name' to match view logic

        return view('roles.edit', compact('role', 'modules', 'rolePermissions'));
    }

    /**
     * Update permissions for a specific role.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if($role->name === 'Super Admin') {
             // Optional: prevent removing perms from Super Admin
        }

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', __('roles.messages.permission_assigned_successfully'));
    }
}