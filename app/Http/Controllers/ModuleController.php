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
        $this->setPageTitle('Modules');
    }
    public function index()
    {
        $modules = Module::all();
        return view('permissions.modules.index', compact('modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:modules,name'
        ]);

        Module::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name)
        ]);

        return redirect()->back()->with('success', 'Module created successfully.');
    }

    public function edit(Module $module)
    {
        return response()->json($module);
    }

    public function update(Request $request, Module $module)
    {
        $request->validate([
            'name' => 'required|unique:modules,name,' . $module->id,
        ]);

        $module->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name)
        ]);

        return redirect()->back()->with('success', 'Module updated successfully.');
    }

    public function destroy(Module $module)
    {
        $module->delete();
        return redirect()->back()->with('success', 'Module deleted successfully.');
    }
}
