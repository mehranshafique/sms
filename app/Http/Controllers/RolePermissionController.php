<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Module;
use App\Models\Permission;
use App\Http\Controllers\BaseController;
class RolePermissionController extends BaseController
{
    public  function __construct(){
        parent::__construct();
        $this->setPageTitle('Role Permissions');
    }
    // Show assignment page
    public function edit(Role $role)
    {
        $modules = Module::with('permissions')->get();
        $rolePermissions = $role->permissions()->pluck('id')->toArray();
        return view('permissions.index');
//        return view('permissions.roles.assign-permissions', compact('role', 'modules', 'rolePermissions'));
    }

    // Assign permissions to role
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array'
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('permissions.roles.index')->with('success', 'Permissions assigned successfully.');
    }
}
