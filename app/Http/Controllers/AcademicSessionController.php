<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AcademicSessionController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(AcademicSession::class, 'academic_session');
    }

    public function index(Request $request)
    {
        // 1. Get Active Context
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = AcademicSession::with('institution')->select('academic_sessions.*');
            
            // 2. Strict Scoping
            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

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
                    
                    if(auth()->user()->can('update', $row)){
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

        // Stats Logic - Scoped
        $query = AcademicSession::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $totalSessions = (clone $query)->count();
        $activeSessions = (clone $query)->where('status', 'active')->count();
        $plannedSessions = (clone $query)->where('status', 'planned')->count();
        $closedSessions = (clone $query)->where('status', 'closed')->count();

        return view('academic_sessions.index', compact('totalSessions', 'activeSessions', 'plannedSessions', 'closedSessions'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        $institutions = [];
        if ($institutionId) {
            // Context Set: Only load the specific institution (Optional, as we will hide the dropdown)
            $institutions = Institution::where('id', $institutionId)->pluck('name', 'id');
        } elseif (Auth::user()->hasRole('Super Admin')) {
            // Global View: Load all
            $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        }

        return view('academic_sessions.create', compact('institutions', 'institutionId'));
    }

    public function store(Request $request)
    {
        // 1. Resolve Institution ID (Context takes priority over Request)
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;

        $validated = $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'name'           => [
                'required', 'string', 'max:50', 
                // Rule: Unique name per institution
                Rule::unique('academic_sessions')->where('institution_id', $institutionId)
            ],
            'start_date'     => 'required|date', 
            'end_date'       => 'required|date|after:start_date', 
            'status'         => 'required|in:planned,active,closed',
            'is_current'     => 'boolean',
        ]);
        
        $validated['institution_id'] = $institutionId;

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
        $institutionId = $this->getInstitutionId();

        // Strict Check: Cannot edit session from another institute if context is set
        if ($institutionId && $academic_session->institution_id != $institutionId) {
            abort(403, 'Unauthorized access.');
        }

        $institutions = Institution::where('id', $academic_session->institution_id)->pluck('name', 'id');
        return view('academic_sessions.edit', compact('academic_session', 'institutions', 'institutionId'));
    }

    public function update(Request $request, AcademicSession $academic_session)
    {
        $institutionId = $this->getInstitutionId();
        
        if ($institutionId && $academic_session->institution_id != $institutionId) {
            abort(403);
        }

        // Use context ID or fallback to existing ID
        $targetId = $institutionId ?? $academic_session->institution_id;

        $validated = $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'name'           => [
                'required', 'string', 'max:50', 
                Rule::unique('academic_sessions')
                    ->ignore($academic_session->id)
                    ->where('institution_id', $targetId)
            ],
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'status'         => 'required|in:planned,active,closed',
            'is_current'     => 'boolean',
        ]);

        if ($institutionId) {
            $validated['institution_id'] = $institutionId;
        }

        DB::transaction(function () use ($validated, $academic_session) {
            if (!empty($validated['is_current']) && $validated['is_current']) {
                AcademicSession::where('institution_id', $academic_session->institution_id)
                    ->where('id', '!=', $academic_session->id)
                    ->update(['is_current' => false]);
            }

            $academic_session->update($validated);
        });

        return response()->json(['message' => __('academic_session.messages.success_update'), 'redirect' => route('academic-sessions.index')]);
    }

    public function destroy(AcademicSession $academic_session)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $academic_session->institution_id != $institutionId) abort(403);

        $academic_session->delete();
        return response()->json(['message' => __('academic_session.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', AcademicSession::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            $institutionId = $this->getInstitutionId();
            
            $query = AcademicSession::whereIn('id', $ids);
            
            // Secure Bulk Delete
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
            
            $query->delete();
            return response()->json(['success' => __('academic_session.messages.success_delete')]);
        }
        return response()->json(['error' => __('academic_session.something_went_wrong')]);
    }
}