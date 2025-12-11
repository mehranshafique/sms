<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Validator;
class RolesController extends BaseController
{
    public function index()
    {
        authorize('roles.view');
        $this->setPageTitle(__('role.page_title'));
        $roles = Role::latest()->get();
        return view('permissions.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        authorize('roles.create');
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::create(['name' => $request->name]);

        return response()->json([
            'message' => __('role.messages.success'),
            'data' => $role
        ]);
    }

    public function update(Request $request, $id)
    {
        authorize('roles.edit');
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);

        return response()->json([
            'message' => __('role.messages.updated'),
            'data' => $role
        ]);
    }

    public function destroy($id)
    {
        authorize('roles.delete');
        Role::findOrFail($id)->delete();
        return redirect()->back()->with('success', __('role.messages.deleted'));
    }
}
