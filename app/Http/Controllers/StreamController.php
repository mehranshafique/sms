<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StreamController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Stream::class, 'stream');
        $this->setPageTitle(__('stream.page_title'));
    }

    /**
     * Helper: Get Accessible Institute IDs for the current user.
     * Logic: Super Admin -> All ([])
     * Single Admin -> [id]
     * Head Officer -> [id, id, ...] (from pivot)
     */
    private function getUserInstituteIds()
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return []; // Empty means all
        }

        if ($user->institute_id) {
            return [$user->institute_id];
        }

        if ($user->institutes && $user->institutes->isNotEmpty()) {
            return $user->institutes->pluck('id')->toArray();
        }

        return [0]; // No access
    }

    public function index(Request $request)
    {
        $allowedInstitutes = $this->getUserInstituteIds();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        if ($request->ajax()) {
            $data = Stream::with('institution')->select('streams.*');

            // STRICT DATA ISOLATION
            if (!$isSuperAdmin) {
                $data->whereIn('institution_id', $allowedInstitutes);
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
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('stream.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('stream.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('streams.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'is_active', 'action'])
                ->make(true);
        }

        // Stats Logic (Scoped)
        $query = Stream::query();
        if (!$isSuperAdmin) {
            $query->whereIn('institution_id', $allowedInstitutes);
        }
        $totalStreams = $query->count();
        $activeStreams = (clone $query)->where('is_active', true)->count();

        return view('streams.index', compact('totalStreams', 'activeStreams'));
    }

    public function create()
    {
        $allowedInstitutes = $this->getUserInstituteIds();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        $institutesQuery = Institution::query();
        if (!$isSuperAdmin) {
            $institutesQuery->whereIn('id', $allowedInstitutes);
        }
        $institutes = $institutesQuery->pluck('name', 'id');

        return view('streams.create', compact('institutes'));
    }

    public function store(Request $request)
    {
        // Enforce that the selected institution is valid for this user
        $allowedInstitutes = $this->getUserInstituteIds();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        $request->validate([
            'institution_id' => [
                'required', 
                'exists:institutions,id',
                // Custom rule: ensure ID is in allowed list if not super admin
                function ($attribute, $value, $fail) use ($allowedInstitutes, $isSuperAdmin) {
                    if (!$isSuperAdmin && !in_array($value, $allowedInstitutes)) {
                        $fail('You are not authorized to create streams for this institution.');
                    }
                },
            ],
            'name' => [
                'required', 'string', 'max:100',
                // Unique Name per Institution
                Rule::unique('streams')->where('institution_id', $request->institution_id)
            ],
            'code' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        Stream::create($request->all());

        return response()->json(['message' => __('stream.messages.success_create'), 'redirect' => route('streams.index')]);
    }

    public function edit(Stream $stream)
    {
        $allowedInstitutes = $this->getUserInstituteIds();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        // Security Check
        if (!$isSuperAdmin && !in_array($stream->institution_id, $allowedInstitutes)) {
            abort(403, 'Unauthorized access to this stream.');
        }

        $institutesQuery = Institution::query();
        if (!$isSuperAdmin) {
            $institutesQuery->whereIn('id', $allowedInstitutes);
        }
        $institutes = $institutesQuery->pluck('name', 'id');

        return view('streams.edit', compact('stream', 'institutes'));
    }

    public function update(Request $request, Stream $stream)
    {
        $allowedInstitutes = $this->getUserInstituteIds();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        // Security Check on the *current* stream being updated
        if (!$isSuperAdmin && !in_array($stream->institution_id, $allowedInstitutes)) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'institution_id' => [
                'required', 
                'exists:institutions,id',
                function ($attribute, $value, $fail) use ($allowedInstitutes, $isSuperAdmin) {
                    if (!$isSuperAdmin && !in_array($value, $allowedInstitutes)) {
                        $fail('Unauthorized institution selection.');
                    }
                },
            ],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('streams')
                    ->ignore($stream->id)
                    ->where('institution_id', $request->institution_id)
            ],
            'code' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        $stream->update($request->all());

        return response()->json(['message' => __('stream.messages.success_update'), 'redirect' => route('streams.index')]);
    }

    public function destroy(Stream $stream)
    {
        $allowedInstitutes = $this->getUserInstituteIds();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        if (!$isSuperAdmin && !in_array($stream->institution_id, $allowedInstitutes)) {
            abort(403);
        }

        $stream->delete();
        return response()->json(['message' => __('stream.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Stream::class);
        
        $ids = $request->ids;
        if (!empty($ids)) {
            $allowedInstitutes = $this->getUserInstituteIds();
            $isSuperAdmin = Auth::user()->hasRole('Super Admin');

            // Secure Bulk Delete: Only delete items belonging to allowed institutes
            $query = Stream::whereIn('id', $ids);
            if (!$isSuperAdmin) {
                $query->whereIn('institution_id', $allowedInstitutes);
            }
            
            $query->delete();
            
            return response()->json(['success' => __('stream.messages.success_delete')]);
        }
        return response()->json(['error' => __('stream.something_went_wrong')]);
    }
}