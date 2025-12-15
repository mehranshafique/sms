<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use App\Models\Institute;

class InstituteController extends Controller
{
    public function __construct()
    {
        // 1. Use resourcePolicy logic
        // This maps 'viewAny' -> index, 'create' -> create/store, etc.
        // Ensure you have an InstitutePolicy registered in AuthServiceProvider
        $this->authorizeResource(Institution::class, 'institute');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Institution::select('*');
            return DataTables::of($data)
                ->addIndexColumn()
                // Checkbox Column for Bulk Actions
                ->addColumn('checkbox', function($row){
                    // Check if user has permission to delete before showing checkbox
                    if(auth()->user()->can('delete', $row)){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('institute.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('institute.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('institutes.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('institute.edit').'">
                                    <i class="fa fa-pencil"></i>
                                </a>';
                    }

                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('institute.delete').'">
                                    <i class="fa fa-trash"></i>
                                </button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'is_active', 'action'])
                ->make(true);
        }
        return view('institutions.index');
    }

    public function create()
    {
        return view('institutions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:30|unique:institutions,code',
            'type' => 'required|in:primary,secondary,university,mixed',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email', // Added based on view inputs
            'is_active' => 'boolean'
        ]);

        Institution::create($validated);

        // 2. Use localization keys
        return response()->json(['message' => __('institute.messages.success_create'), 'redirect' => route('institutes.index')]);
    }

    public function edit(Institution $institute)
    {
        return view('institutions.edit', compact('institute'));
    }

    public function update(Request $request, Institution $institute)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:30|unique:institutions,code,'.$institute->id,
            'type' => 'required|in:primary,secondary,university,mixed',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'is_active' => 'boolean'
        ]);

        $institute->update($validated);

        return response()->json(['message' => __('institute.messages.success_update'), 'redirect' => route('institutes.index')]);
    }

    public function destroy(Institution $institute)
    {
        $institute->delete();
        return response()->json(['message' => __('institute.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        // For bulk delete, we can check a general permission or loop through
        // Typically, we check 'deleteAny' or similar, but here manual check
        $this->authorize('deleteAny', Institution::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            Institution::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('institute.messages.success_delete')]);
        }
        return response()->json(['error' => __('institute.something_went_wrong')]);
    }
}