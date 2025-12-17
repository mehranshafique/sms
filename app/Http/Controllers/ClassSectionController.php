<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Institution;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\Staff;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class ClassSectionController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(ClassSection::class, 'class_section');
        $this->setPageTitle(__('class_section.page_title'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ClassSection::with(['gradeLevel', 'classTeacher.user', 'campus'])
                ->select('class_sections.*');

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
                ->addColumn('details', function($row){
                    return '<div class="d-flex flex-column">
                                <span class="fw-bold text-primary">'.$row->name.'</span>
                                <span class="fs-12 text-muted">Room: '.($row->room_number ?? 'N/A').'</span>
                            </div>';
                })
                ->addColumn('grade', function($row){
                    return $row->gradeLevel->name ?? 'N/A';
                })
                ->addColumn('teacher', function($row){
                    return $row->classTeacher ? $row->classTeacher->user->name : '<span class="badge badge-warning">'.__('class_section.not_assigned').'</span>';
                })
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('class_section.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('class_section.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('class-sections.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'details', 'teacher', 'is_active', 'action'])
                ->make(true);
        }

        // Stats
        $totalClasses = ClassSection::count();
        $activeClasses = ClassSection::where('is_active', true)->count();
        // Assuming GradeLevel scopes can be used or raw counts
        // Just generic stats for now
        $totalCapacity = ClassSection::sum('capacity');

        return view('class_sections.index', compact('totalClasses', 'activeClasses', 'totalCapacity'));
    }

    public function create()
    {
        $campuses = Campus::where('is_active', true)->pluck('name', 'id');
        $gradeLevels = GradeLevel::orderBy('order_index')->pluck('name', 'id');
        
        // Fetch staff with 'Teacher' role if possible, or just all staff for now
        // Assuming Staff model has user relation loaded for names
        $staff = Staff::with('user')->get()->mapWithKeys(function ($item) {
            return [$item->id => $item->user->name . ' (' . ($item->employee_id ?? 'N/A') . ')'];
        });

        return view('class_sections.create', compact('campuses', 'gradeLevels', 'staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id', // Usually set hidden or via middleware
            'campus_id'      => 'nullable|exists:campuses,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name'           => ['required', 'string', 'max:100', 
                Rule::unique('class_sections')->where('grade_level_id', $request->grade_level_id)
            ],
            'code'           => 'nullable|string|max:30',
            'room_number'    => 'nullable|string|max:50',
            'capacity'       => 'required|integer|min:1',
            'staff_id'       => 'nullable|exists:staff,id',
            'is_active'      => 'boolean'
        ]);

        ClassSection::create($validated);

        return response()->json(['message' => __('class_section.messages.success_create'), 'redirect' => route('class-sections.index')]);
    }

    public function edit(ClassSection $class_section)
    {
        $campuses = Campus::where('is_active', true)->pluck('name', 'id');
        $gradeLevels = GradeLevel::orderBy('order_index')->pluck('name', 'id');
        $staff = Staff::with('user')->get()->mapWithKeys(function ($item) {
            return [$item->id => $item->user->name . ' (' . ($item->employee_id ?? 'N/A') . ')'];
        });

        return view('class_sections.edit', compact('class_section', 'campuses', 'gradeLevels', 'staff'));
    }

    public function update(Request $request, ClassSection $class_section)
    {
        $validated = $request->validate([
            'campus_id'      => 'nullable|exists:campuses,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name'           => ['required', 'string', 'max:100', 
                Rule::unique('class_sections')
                    ->ignore($class_section->id)
                    ->where('grade_level_id', $request->grade_level_id)
            ],
            'code'           => 'nullable|string|max:30',
            'room_number'    => 'nullable|string|max:50',
            'capacity'       => 'required|integer|min:1',
            'staff_id'       => 'nullable|exists:staff,id',
            'is_active'      => 'boolean'
        ]);

        $class_section->update($validated);

        return response()->json(['message' => __('class_section.messages.success_update'), 'redirect' => route('class-sections.index')]);
    }

    public function destroy(ClassSection $class_section)
    {
        $class_section->delete();
        return response()->json(['message' => __('class_section.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', ClassSection::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            ClassSection::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('class_section.messages.success_delete')]);
        }
        return response()->json(['error' => __('class_section.something_went_wrong')]);
    }
}