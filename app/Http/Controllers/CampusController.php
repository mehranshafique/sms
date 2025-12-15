<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class CampusController extends BaseController
{
    public function __construct()
    {
        // This requires CampusPolicy to be created and registered
        $this->authorizeResource(Campus::class, 'campus');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Campus::with('institution')->select('campuses.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    if(auth()->user()->can('delete', $row)){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('institution_name', function($row){
                    return $row->institution->name ?? '';
                })
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('campus.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('campus.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('edit', $row)){
                        $btn .= '<a href="'.route('campuses.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('campus.edit').'">
                                    <i class="fa fa-pencil"></i>
                                </a>';
                    }

                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('campus.delete').'">
                                    <i class="fa fa-trash"></i>
                                </button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'is_active', 'action'])
                ->make(true);
        }

        // Stats Logic
        $totalCampuses = Campus::count();
        $activeCampuses = Campus::where('is_active', true)->count();
        $inactiveCampuses = Campus::where('is_active', false)->count();
        $newCampuses = Campus::where('created_at', '>=', now()->subMonth())->count();

        return view('campuses.index', compact('totalCampuses', 'activeCampuses', 'inactiveCampuses', 'newCampuses'));
    }

    public function create()
    {
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        return view('campuses.create', compact('institutions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'name' => 'required|string|max:150',
            // Code unique per institution
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('campuses')->where(function ($query) use ($request) {
                    return $query->where('institution_id', $request->institution_id);
                })
            ],
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'is_active' => 'boolean'
        ]);

        Campus::create($validated);

        return response()->json(['message' => __('campus.messages.success_create'), 'redirect' => route('campuses.index')]);
    }

    public function edit(Campus $campus)
    {
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        return view('campuses.edit', compact('campus', 'institutions'));
    }

    public function update(Request $request, Campus $campus)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'name' => 'required|string|max:150',
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('campuses')->ignore($campus->id)->where(function ($query) use ($request) {
                    return $query->where('institution_id', $request->institution_id);
                })
            ],
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'is_active' => 'boolean'
        ]);

        $campus->update($validated);

        return response()->json(['message' => __('campus.messages.success_update'), 'redirect' => route('campuses.index')]);
    }

    public function destroy(Campus $campus)
    {
        $campus->delete();
        return response()->json(['message' => __('campus.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Campus::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            Campus::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('campus.messages.success_delete')]);
        }
        return response()->json(['error' => __('campus.something_went_wrong')]);
    }
}