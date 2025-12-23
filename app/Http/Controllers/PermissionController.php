<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use App\Models\Module;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PermissionController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle(__('roles.permissions_page_title'));
        // Using Spatie Middleware for granular control if Policy not generated
        // $this->middleware('permission:permission.view')->only('index');
    }

    public function index($moduleId)
    {
        $module = Module::findOrFail($moduleId);
        $permissions = Permission::where('module_id', $moduleId)->get();
        
        return view('permissions.index', compact('permissions', 'module'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'module_id' => 'required|exists:modules,id'
        ]);

        $module = Module::findOrFail($request->module_id);
        
        // Auto-generate name format: module.action
        $permName = $module->name . '.' . $request->name; // e.g. Student.create (Ensure Module name is slug-friendly in DB)

        Permission::create([
            'name' => strtolower(str_replace(' ', '_', $permName)),
            'module_id' => $request->module_id,
            'guard_name' => 'web'
        ]);

        return response()->json(['message' => __('roles.messages.permission_created')]);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        // Keep prefix logic or allow full rename?
        // Usually better to keep module prefix consistency
        $prefix = explode('.', $permission->name)[0] ?? '';
        $newName = $prefix ? $prefix . '.' . $request->name : $request->name;

        $permission->update([
            'name' => strtolower(str_replace(' ', '_', $newName)),
        ]);

        return response()->json(['message' => __('roles.messages.permission_updated')]);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(['message' => __('roles.messages.permission_deleted')]);
    }
}