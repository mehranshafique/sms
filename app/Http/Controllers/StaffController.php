<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use App\Models\Institution;
use App\Models\Campus;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StaffController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Staff::class, 'staff');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Staff::with(['user', 'institution', 'campus'])->select('staff.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                <label class="form-check-label"></label>
                            </div>';
                })
                ->addColumn('details', function($row){
                    $img = $row->user->profile_picture ? asset('storage/' . $row->user->profile_picture) : null;
                    $name = $row->user->name;
                    $email = $row->user->email;
                    $initial = strtoupper(substr($name, 0, 1));
                    
                    $avatarHtml = $img 
                        ? '<img src="'.$img.'" class="rounded-circle me-3" width="50" height="50" alt="">'
                        : '<div class="head-officer-icon bgl-primary text-primary position-relative me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold;">'.$initial.'</div>';

                    return '<div class="d-flex align-items-center">
                                '.$avatarHtml.'
                                <div>
                                    <h6 class="fs-16 font-w600 mb-0"><a href="'.route('staff.show', $row->id).'" class="text-black">'.$name.'</a></h6>
                                    <span class="fs-13 text-muted">'.$email.'</span>
                                </div>
                            </div>';
                })
                ->addColumn('role', function($row){
                    return $row->user->roles->pluck('name')->join(', ');
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'active' => 'badge-success',
                        'on_leave' => 'badge-warning',
                        'resigned' => 'badge-secondary',
                        'terminated' => 'badge-danger'
                    ];
                    return '<span class="badge '.($badges[$row->status] ?? 'badge-light').'">'.ucfirst(str_replace('_', ' ', $row->status)).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    $btn .= '<a href="'.route('staff.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    $btn .= '<a href="'.route('staff.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    $btn .= '<button class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'details', 'role', 'status', 'action'])
                ->make(true);
        }

        return view('staff.index');
    }

    public function create()
    {
        $institutes = Institution::pluck('name', 'id');
        $campuses = Campus::pluck('name', 'id');
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        return view('staff.create', compact('institutes', 'campuses', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // User Fields
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,name',
            'phone' => 'nullable|string',
            'profile_picture' => 'nullable|image|max:2048',
            
            // Staff Fields
            'institution_id' => 'required|exists:institutions,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'designation' => 'nullable|string',
            'joining_date' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Create User
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'user_type' => 4, // 4 = Staff
                'is_active' => true,
            ];

            if ($request->hasFile('profile_picture')) {
                $userData['profile_picture'] = $request->file('profile_picture')->store('profile-photos', 'public');
            }

            $user = User::create($userData);
            $user->assignRole($request->role);

            // 2. Create Staff Profile
            Staff::create([
                'user_id' => $user->id,
                'institution_id' => $request->institution_id,
                'campus_id' => $request->campus_id,
                'employee_id' => $request->employee_id ?? 'EMP-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'designation' => $request->designation,
                'department' => $request->department,
                'joining_date' => $request->joining_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'status' => 'active',
            ]);
        });

        return response()->json(['redirect' => route('staff.index'), 'message' => __('staff.messages.success_create')]);
    }

    public function show(Staff $staff)
    {
        $staff->load(['user', 'institution', 'campus']);
        return view('staff.show', compact('staff'));
    }

    public function edit(Staff $staff)
    {
        $staff->load('user');
        $institutes = Institution::pluck('name', 'id');
        $campuses = Campus::pluck('name', 'id');
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        
        return view('staff.edit', compact('staff', 'institutes', 'campuses', 'roles'));
    }

    public function update(Request $request, Staff $staff)
    {
        $user = $staff->user;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|exists:roles,name',
            'profile_picture' => 'nullable|image|max:2048',
            'institution_id' => 'required|exists:institutions,id',
        ]);

        DB::transaction(function () use ($request, $staff, $user) {
            // Update User
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ];

            if ($request->password) {
                $userData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $userData['profile_picture'] = $request->file('profile_picture')->store('profile-photos', 'public');
            }

            $user->update($userData);
            $user->syncRoles([$request->role]);

            // Update Staff
            $staff->update([
                'institution_id' => $request->institution_id,
                'campus_id' => $request->campus_id,
                'employee_id' => $request->employee_id,
                'designation' => $request->designation,
                'department' => $request->department,
                'joining_date' => $request->joining_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'status' => $request->status ?? $staff->status,
            ]);
        });

        return response()->json(['redirect' => route('staff.index'), 'message' => __('staff.messages.success_update')]);
    }

    public function destroy(Staff $staff)
    {
        $user = $staff->user;
        
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }
        
        $staff->delete(); // Delete profile
        $user->delete();  // Delete login
        
        return response()->json(['message' => __('staff.messages.success_delete')]);
    }
}