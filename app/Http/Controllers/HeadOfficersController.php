<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class HeadOfficersController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('head_officers.page_title'));
    }

    public function index(Request $request)
    {
        authorize('head_officers.view');

        if ($request->ajax()) {
            // Eager load relationships to avoid N+1 and access sub-table data
            $data = User::where('user_type', 2)->with(['institute', 'staff'])->select('users.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    if(auth()->user()->can('head_officers.delete')){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('details', function($row){
                    $initial = strtoupper(substr($row->name, 0, 1));
                    // Check staff status if available, otherwise fallback
                    $statusDot = '';
                    if ($row->staff) {
                        $statusClass = $row->staff->status == 1 ? 'border-success' : 'border-danger';
                        $statusDot = '<span class="position-absolute bottom-0 end-0 p-1 bg-white border '.$statusClass.' border-2 rounded-circle"></span>';
                    }

                    return '<div class="d-flex align-items-center">
                                <div class="head-officer-icon bgl-primary text-primary position-relative">
                                    '.$initial.'
                                    '.$statusDot.'
                                </div>
                                <div>
                                    <h6 class="fs-16 font-w600 mb-0"><a href="javascript:void(0)" class="text-black">'.$row->name.'</a></h6>
                                    <span class="fs-13 text-muted">'.$row->email.'</span>
                                </div>
                            </div>';
                })
                ->addColumn('contact', function($row){
                    return '<div class="d-flex flex-column">
                                <span class="fs-13">'.$row->phone.'</span>
                                <span class="fs-12 text-muted">'.Str::limit($row->address, 20).'</span>
                            </div>';
                })
                ->addColumn('total_institution', function($row){
                    // Show assigned institute name
                    $instituteName = $row->institute ? $row->institute->name : 'N/A';
                    return '<span class="badge badge-pill badge-secondary">'.$instituteName.'</span>';
                })
                ->addColumn('status', function($row){
                    // Status comes from the Staff sub-table
                    if ($row->staff) {
                        if ($row->staff->status == 1) {
                            return '<span class="badge badge-success">Active</span>';
                        }
                        return '<span class="badge badge-danger">Inactive</span>';
                    }
                    return '<span class="badge badge-warning">N/A</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('head_officers.update')){
                        $btn .= '<button type="button" class="btn btn-primary shadow btn-xs sharp me-1 edit-btn" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#editHeaderOfficerModal" title="'.__('head_officers.edit_head_officer').'">
                                    <i class="fa fa-pencil"></i>
                                </button>';
                    }

                    if(auth()->user()->can('head_officers.delete')){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('head_officers.delete').'">
                                    <i class="fa fa-trash"></i>
                                </button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'details', 'contact', 'total_institution', 'status', 'action'])
                ->make(true);
        }

        // Stats Logic using relationships
        // Note: Ensure User model has public function staff() { return $this->hasOne(Staff::class); }
        $totalOfficers = User::where('user_type', 2)->count();
        
        $activeOfficers = User::where('user_type', 2)
            ->whereHas('staff', function($q) {
                $q->where('status', 1);
            })->count();

        $inactiveOfficers = User::where('user_type', 2)
            ->whereHas('staff', function($q) {
                $q->where('status', 0);
            })->count();

        $newThisMonth = User::where('user_type', 2)
            ->where('created_at', '>=', now()->subMonth())
            ->count();

        return view('head_officers.index', compact('totalOfficers', 'activeOfficers', 'inactiveOfficers', 'newThisMonth'));
    }

    public function store(Request $request)
    {
        authorize('head_officers.create');
        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'address'  => 'required|string',
            // 'institute_id' => 'required|exists:institutes,id', // Recommended to add this
        ]);

        // 1. Create User
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'user_type' => 2, // Head Officer
            'address'   => $request->address,
            'password'  => Hash::make($request->password),
            // 'institute_id' => $request->institute_id, 
        ]);
        
        // 2. Create associated Staff record for Status/Profile management
        Staff::create([
            'user_id' => $user->id,
            'institute_id' => $user->institute_id, // Sync institute if applicable
            'status' => 1, // Default Active
            'designation' => 'Head Officer',
            'hire_date' => now(),
        ]);

        // $user->assignRole('Head Officer');

        return response()->json([
            'status'  => true,
            'message' => __('head_officers.messages.created'),
            'data'    => $user
        ]);
    }

    public function edit($id)
    {
        $officer = User::with('staff')->findOrFail($id);
        return response()->json($officer);
    }

    public function update(Request $request, $id)
    {
        authorize('head_officers.update');
        $officer = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email,' . $officer->id,
            'phone'    => 'required|string|max:20',
            'address'  => 'required|string',
            'password' => 'nullable|min:6',
        ]);

        $officer->name    = $request->name;
        $officer->email   = $request->email;
        $officer->phone   = $request->phone;
        $officer->address = $request->address;

        if ($request->password) {
            $officer->password = Hash::make($request->password);
        }

        $officer->save();
        
        // Optionally update staff details/status here if form supports it
        // if ($request->has('status')) {
        //     $officer->staff()->update(['status' => $request->status]);
        // }

        return response()->json([
            'status'  => true,
            'message' => __('head_officers.messages.updated'),
        ]);
    }

    public function destroy($id)
    {
        authorize('head_officers.delete');
        $officer = User::findOrFail($id);
        
        // Delete related staff record first or rely on cascade
        if($officer->staff) {
            $officer->staff()->delete();
        }
        
        $officer->delete();

        return response()->json([
            'status'  => true,
            'message' => __('head_officers.messages.deleted'),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        authorize('head_officers.delete');
        $ids = $request->ids;
        if (!empty($ids)) {
            // Delete Staff records first
            Staff::whereIn('user_id', $ids)->delete();
            // Delete Users
            User::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('head_officers.messages.deleted')]);
        }
        return response()->json(['error' => __('head_officers.something_went_wrong')]);
    }
}