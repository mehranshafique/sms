<?php

namespace App\Http\Controllers;

use App\Models\StaffLeave;
use App\Models\Staff;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StaffLeaveController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('staff_leave.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if ($request->ajax()) {
            $query = StaffLeave::with(['staff.user'])
                ->where('institution_id', $institutionId)
                ->latest();

            // Staff view own leaves only
            if ($user->hasRole(RoleEnum::TEACHER->value) || ($user->staff && !$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value]))) {
                $query->where('staff_id', $user->staff->id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('staff_name', fn($row) => $row->staff->user->name ?? 'N/A')
                ->editColumn('type', fn($row) => __('staff_leave.type_' . $row->type))
                ->editColumn('status', function($row){
                    $badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    return '<span class="badge badge-' . ($badges[$row->status] ?? 'secondary') . '">' . __('staff_leave.status_' . $row->status) . '</span>';
                })
                ->addColumn('dates', function($row){
                    $dates = $row->start_date->format('d M');
                    if($row->end_date) $dates .= ' - ' . $row->end_date->format('d M, Y');
                    return $dates;
                })
                ->addColumn('action', function($row) use ($user) {
                    $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);
                    
                    $btn = '<div class="d-flex justify-content-end">';
                    
                    // Admin Approve/Reject
                    if ($isAdmin && $row->status === 'pending') {
                        $btn .= '<button class="btn btn-success btn-xs shadow me-1 update-status" data-id="'.$row->id.'" data-status="approved" title="'.__('staff_leave.approve').'"><i class="fa fa-check"></i></button>';
                        $btn .= '<button class="btn btn-danger btn-xs shadow me-1 update-status" data-id="'.$row->id.'" data-status="rejected" title="'.__('staff_leave.reject').'"><i class="fa fa-times"></i></button>';
                    }
                    
                    // View
                    $btn .= '<a href="'.route('staff-leaves.show', $row->id).'" class="btn btn-info btn-xs shadow me-1"><i class="fa fa-eye"></i></a>';
                    
                    // Delete (If pending or Admin)
                    if ($row->status === 'pending' || $isAdmin) {
                        $btn .= '<button class="btn btn-danger btn-xs shadow delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('staff_leaves.index');
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();
        $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);
        
        $staffMembers = [];
        if($isAdmin) {
            $staffMembers = Staff::with('user')
                ->where('institution_id', $institutionId)
                ->where('status', 'active')
                ->get()
                ->mapWithKeys(fn($s) => [$s->id => $s->user->name]);
        }
        
        return view('staff_leaves.create', compact('isAdmin', 'staffMembers'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();
        $isAdmin = $user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value]);

        $request->validate([
            'staff_id' => $isAdmin ? 'required|exists:staff,id' : 'nullable',
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048'
        ]);

        $targetStaffId = $isAdmin ? $request->staff_id : ($user->staff->id ?? null);
        
        if(!$targetStaffId) abort(403, 'Staff profile not found.');

        $path = null;
        if($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('leaves', 'public');
        }

        StaffLeave::create([
            'institution_id' => $institutionId,
            'staff_id' => $targetStaffId,
            'type' => $request->type,
            'reason' => $request->reason,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $isAdmin ? 'approved' : 'pending', // Auto-approve if admin created
            'action_by' => $isAdmin ? $user->id : null,
            'file_path' => $path
        ]);

        return redirect()->route('staff-leaves.index')->with('success', __('staff_leave.success_create'));
    }

    public function show($id)
    {
        $leave = StaffLeave::with(['staff.user', 'actioner'])->findOrFail($id);
        if ($this->getInstitutionId() != $leave->institution_id) abort(403);
        
        return view('staff_leaves.show', compact('leave'));
    }

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value])) {
            return response()->json(['message' => __('staff_leave.unauthorized')], 403);
        }

        $leave = StaffLeave::findOrFail($id);
        $leave->update([
            'status' => $request->status,
            'action_by' => $user->id,
            'admin_remarks' => $request->remarks
        ]);

        return response()->json(['message' => __('staff_leave.success_update')]);
    }

    public function destroy($id)
    {
        $leave = StaffLeave::findOrFail($id);
        // Add ownership check here
        if($leave->file_path) Storage::disk('public')->delete($leave->file_path);
        $leave->delete();
        return response()->json(['message' => __('staff_leave.success_delete')]);
    }
}