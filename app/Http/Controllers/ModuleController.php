<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use Illuminate\Support\Str;
use App\Http\Controllers\BaseController;
class ModuleController extends BaseController
{
    public  function __construct(){
        parent::__construct();
        $this->setPageTitle(__('modules.page_title'));
    }
    public function index()
    {
        authorize('module.view');
        $modules = Module::all();
        return view('permissions.modules.index', compact('modules'));
    }

    public function store(Request $request)
    {
        authorize('module.create');
        $request->validate([
            'name' => 'required|unique:modules,name'
        ]);

        Module::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name)
        ]);

        return redirect()->back()->with('success', __('modules.messages.success_create'));
    }

    public function edit(Module $module)
    {
        return response()->json($module);
    }

    public function update(Request $request, Module $module)
    {
        authorize('module.edit');
        $request->validate([
            'name' => 'required|unique:modules,name,' . $module->id,
        ]);

        $module->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name)
        ]);

        return redirect()->back()->with('success', __('modules.messages.success_update'));
    }

    public function destroy(Module $module)
    {
        authorize('module.delete');
        $module->delete();
        return redirect()->back()->with('success', __('modules.messages.success_delete'));
    }
}
