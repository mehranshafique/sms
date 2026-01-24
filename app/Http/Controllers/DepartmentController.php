<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Institution;
use App\Models\Staff;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Secure resource actions with 'department' policy
        $this->authorizeResource(Department::class, 'department');
        $this->setPageTitle(__('department.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = Department::with(['headOfDepartment.user', 'institution'])
                ->select('departments.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('head_of_department', function($row){
                    return $row->headOfDepartment ? $row->headOfDepartment->user->name : 'N/A';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('departments.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('departments.index');
    }

    // ... (rest of methods create, store, edit, update, destroy remain same as previous response) ...
    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        $institutions = [];
        if (!$institutionId && Auth::user()->hasRole('Super Admin')) {
            $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        }

        $staffQuery = Staff::with('user');
        if ($institutionId) {
            $staffQuery->where('institution_id', $institutionId);
        }
        $staff = $staffQuery->get()->mapWithKeys(function ($item) {
            return [$item->id => $item->user->name . ' (' . ($item->employee_id ?? 'N/A') . ')'];
        });

        return view('departments.create', compact('institutions', 'staff', 'institutionId'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;

        $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'name' => ['required', 'string', 'max:100', 
                Rule::unique('departments')->where('institution_id', $institutionId)
            ],
            'code' => 'nullable|string|max:20',
            'head_of_department_id' => 'nullable|exists:staff,id',
        ]);

        Department::create([
            'institution_id' => $institutionId,
            'name' => $request->name,
            'code' => $request->code,
            'head_of_department_id' => $request->head_of_department_id,
        ]);

        return response()->json(['message' => __('department.messages.success_create'), 'redirect' => route('departments.index')]);
    }

    public function edit(Department $department)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $department->institution_id != $institutionId) abort(403);

        $institutions = Institution::where('id', $department->institution_id)->pluck('name', 'id');
        
        $staff = Staff::with('user')
            ->where('institution_id', $department->institution_id)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->user->name . ' (' . ($item->employee_id ?? 'N/A') . ')'];
            });

        return view('departments.edit', compact('department', 'institutions', 'staff', 'institutionId'));
    }

    public function update(Request $request, Department $department)
    {
        $institutionId = $this->getInstitutionId() ?? $department->institution_id;

        $request->validate([
            'name' => ['required', 'string', 'max:100', 
                Rule::unique('departments')->ignore($department->id)->where('institution_id', $institutionId)
            ],
            'code' => 'nullable|string|max:20',
            'head_of_department_id' => 'nullable|exists:staff,id',
        ]);

        $department->update([
            'name' => $request->name,
            'code' => $request->code,
            'head_of_department_id' => $request->head_of_department_id,
        ]);

        return response()->json(['message' => __('department.messages.success_update'), 'redirect' => route('departments.index')]);
    }

    public function destroy(Department $department)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $department->institution_id != $institutionId) abort(403);

        $department->delete();
        return response()->json(['message' => __('department.messages.success_delete')]);
    }
}