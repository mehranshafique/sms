<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\FeeType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class FeeTypeController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':fee_type.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':fee_type.create')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':fee_type.update')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':fee_type.delete')->only(['destroy']);
        
        $this->setPageTitle(__('finance.fee_type_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = FeeType::select('*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">Active</span>' 
                        : '<span class="badge badge-danger">Inactive</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('fee_type.update')){
                        // Edit Button triggers Modal
                        $btn .= '<button type="button" 
                                    class="btn btn-primary shadow btn-xs sharp me-1 edit-btn" 
                                    data-id="'.$row->id.'" 
                                    data-name="'.$row->name.'" 
                                    data-description="'.$row->description.'" 
                                    data-status="'.$row->is_active.'">
                                    <i class="fa fa-pencil"></i>
                                </button>';
                    }
                    if(auth()->user()->can('fee_type.delete')){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('finance.fee_types.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $institutionId = $this->getInstitutionId();

        if(!$institutionId) {
             return response()->json(['message' => 'Super Admin must select a context or manually assign ID.'], 422);
        }

        FeeType::create([
            'institution_id' => $institutionId,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json(['message' => __('finance.success_create_type'), 'redirect' => route('fee-types.index')]);
    }

    public function update(Request $request, $id)
    {
        $feeType = FeeType::findOrFail($id);
        $institutionId = $this->getInstitutionId();

        if ($institutionId && $feeType->institution_id != $institutionId) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $feeType->update($request->all());

        return response()->json(['message' => __('finance.success_update_type'), 'redirect' => route('fee-types.index')]);
    }

    public function destroy($id)
    {
        $feeType = FeeType::findOrFail($id);
        $institutionId = $this->getInstitutionId();

        if ($institutionId && $feeType->institution_id != $institutionId) {
            abort(403);
        }

        $feeType->delete();
        return response()->json(['message' => __('finance.success_delete_type')]);
    }
}