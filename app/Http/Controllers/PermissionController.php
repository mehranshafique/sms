<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Module;
use App\Http\Controllers\BaseController;
class PermissionController extends BaseController
{
    public function __construct(){
        parent::__construct();
        $this->setPageTitle('Permissions');
    }
    public function index()
    {
        $permissions = Permission::with('module')->get();
        return view('permissions.permissions.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
            'module_id' => 'required|exists:modules,id'
        ]);

        Permission::create([
            'name' => $request->name,
            'module_id' => $request->module_id,
            'guard_name' => 'web'
        ]);

        return redirect()->back()->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        return response()->json($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
            'module_id' => 'required|exists:modules,id'
        ]);

        $permission->update([
            'name' => $request->name,
            'module_id' => $request->module_id
        ]);

        return redirect()->back()->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }
}
