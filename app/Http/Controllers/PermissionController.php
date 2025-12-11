<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Module;
use App\Http\Controllers\BaseController;
use App\Models\User;
class PermissionController extends BaseController
{
    public function __construct(){
        parent::__construct();
        $this->setPageTitle(__('modules.permissions_page_title'));
    }
    public function index($id)
    {
        authorize('permissions.view');
        $module = Module::find($id);
        $permissions = Permission::with('module')->where('module_id',$id)->get();
        return view('permissions.permissions.index', compact('permissions','module'));
    }

    public function store(Request $request)
    {
        authorize('permissions.create');
        $request->validate([
            'name' => 'required',
            'module_id' => 'required|exists:modules,id'
        ]);
        $module = Module::find($request->module_id);
//        dd($module);.
        Permission::create([
            'name' => $module->slug . '.' . $request->name,
            'module_id' => $request->module_id,
            'guard_name' => 'web'
        ]);

        return redirect()->back()->with('success', __('modules.permission_messages.success_create'));
    }

    public function edit(Permission $permission)
    {
        $permission->name = explode('.', $permission->name)[1];
        return response()->json($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        authorize('permissions.edit');
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
            'module_id' => 'required|exists:modules,id'
        ]);

        $permission->update([
            'name' => $permission->module->slug . '.' . $request->name,
            'module_id' => $request->module_id
        ]);

        return redirect()->back()->with('success', __('modules.permission_messages.success_update'));
    }

    public function destroy(Permission $permission)
    {
        authorize('permissions.delete');
        $permission->delete();
        return redirect()->back()->with('success', __('modules.permission_messages.success_delete'));
    }
}
