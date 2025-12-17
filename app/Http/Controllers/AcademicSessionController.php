<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class AcademicSessionController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(AcademicSession::class, 'academic_session');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = AcademicSession::with('institution')->select('academic_sessions.*');
            
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
                    return $row->institution->name ?? 'N/A';
                })
                ->editColumn('is_current', function($row){
                    return $row->is_current 
                        ? '<span class="badge badge-success">'.__('academic_session.yes').'</span>' 
                        : '<span class="badge badge-secondary">'.__('academic_session.no').'</span>';
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'active' => 'badge-success',
                        'planned' => 'badge-info',
                        'closed' => 'badge-danger',
                    ];
                    $class = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge '.$class.'">'.ucfirst($row->status).'</span>';
                })
                ->editColumn('start_date', function($row){
                    return $row->start_date ? $row->start_date->format('d F, Y') : '-';
                })
                ->editColumn('end_date', function($row){
                    return $row->end_date ? $row->end_date->format('d F, Y') : '-';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('edit', $row)){
                        $btn .= '<a href="'.route('academic-sessions.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('academic_session.edit').'">
                                    <i class="fa fa-pencil"></i>
                                </a>';
                    }

                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('academic_session.delete').'">
                                    <i class="fa fa-trash"></i>
                                </button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'is_current', 'status', 'action'])
                ->make(true);
        }

        // Stats Logic
        $totalSessions = AcademicSession::count();
        $activeSessions = AcademicSession::where('status', 'active')->count();
        $plannedSessions = AcademicSession::where('status', 'planned')->count();
        $closedSessions = AcademicSession::where('status', 'closed')->count();

        return view('academic_sessions.index', compact('totalSessions', 'activeSessions', 'plannedSessions', 'closedSessions'));
    }

    public function create()
    {
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        return view('academic_sessions.create', compact('institutions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'name'           => ['required', 'string', 'max:50', Rule::unique('academic_sessions')->where('institution_id', $request->institution_id)],
            // Use 'required' instead of 'date' to allow flexibility for the picker format; Mutator handles parsing
            'start_date'     => 'required', 
            'end_date'       => 'required', 
            'status'         => 'required|in:planned,active,closed',
            'is_current'     => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            // Ensure only one current session per institution
            if (!empty($validated['is_current']) && $validated['is_current']) {
                AcademicSession::where('institution_id', $validated['institution_id'])
                    ->update(['is_current' => false]);
            }

            AcademicSession::create($validated);
        });

        return response()->json(['message' => __('academic_session.messages.success_create'), 'redirect' => route('academic-sessions.index')]);
    }

    public function edit(AcademicSession $academic_session)
    {
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        return view('academic_sessions.edit', compact('academic_session', 'institutions'));
    }

    public function update(Request $request, AcademicSession $academic_session)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'name'           => ['required', 'string', 'max:50', Rule::unique('academic_sessions')->ignore($academic_session->id)->where('institution_id', $request->institution_id)],
            'start_date'     => 'required',
            'end_date'       => 'required',
            'status'         => 'required|in:planned,active,closed',
            'is_current'     => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $academic_session) {
            if (!empty($validated['is_current']) && $validated['is_current']) {
                AcademicSession::where('institution_id', $validated['institution_id'])
                    ->where('id', '!=', $academic_session->id)
                    ->update(['is_current' => false]);
            }

            $academic_session->update($validated);
        });

        return response()->json(['message' => __('academic_session.messages.success_update'), 'redirect' => route('academic-sessions.index')]);
    }

    public function destroy(AcademicSession $academic_session)
    {
        $academic_session->delete();
        return response()->json(['message' => __('academic_session.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', AcademicSession::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            AcademicSession::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('academic_session.messages.success_delete')]);
        }
        return response()->json(['error' => __('academic_session.something_went_wrong')]);
    }
}