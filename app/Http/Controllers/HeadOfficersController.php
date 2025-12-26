<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Institution;
use App\Services\NotificationService; // Injected Service
use App\Enums\RoleEnum; // Using Enums
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;

class HeadOfficersController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('user_type', 2)
                ->with(['institutes', 'roles'])
                ->select('users.*')
                ->latest(); 

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                <label class="form-check-label"></label>
                            </div>';
                })
                ->addColumn('details', function($row){
                    $img = $row->profile_picture ? asset('storage/' . $row->profile_picture) : null;
                    $initial = strtoupper(substr($row->name, 0, 1));
                    
                    $avatarHtml = $img 
                        ? '<img src="'.$img.'" class="rounded-circle me-3" width="50" height="50" alt="">'
                        : '<div class="head-officer-icon bgl-primary text-primary position-relative me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold;">'.$initial.'</div>';

                    return '<div class="d-flex align-items-center">
                                '.$avatarHtml.'
                                <div>
                                    <h6 class="fs-16 font-w600 mb-0"><a href="'.route('header-officers.show', $row->id).'" class="text-black">'.$row->name.'</a></h6>
                                    <span class="fs-13 text-muted">'.$row->email.'</span>
                                </div>
                            </div>';
                })
                ->addColumn('contact', function($row){
                    return '<div>'.$row->phone.'</div>';
                })
                ->addColumn('assigned_institutes', function($row){
                    // Display codes of assigned institutes
                    return $row->institutes->pluck('code')->join(', ');
                })
                ->addColumn('role', function($row){
                    // Display roles (unique names to avoid repetition if multiple Head Officer roles)
                    return $row->roles->pluck('name')->unique()->join(', ');
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    $btn .= '<a href="'.route('header-officers.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    $btn .= '<a href="'.route('header-officers.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    $btn .= '<button class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'details', 'contact', 'assigned_institutes', 'role', 'action'])
                ->make(true);
        }

        $totalOfficers = User::where('user_type', 2)->count();
        $activeOfficers = User::where('user_type', 2)->where('is_active', true)->count();
        $inactiveOfficers = User::where('user_type', 2)->where('is_active', false)->count();
        $newThisMonth = User::where('user_type', 2)->where('created_at', '>=', now()->subMonth())->count();

        return view('head_officers.index', compact('totalOfficers', 'activeOfficers', 'inactiveOfficers', 'newThisMonth'));
    }

    public function create()
    {
        $institutes = Institution::where('is_active', true)->pluck('name', 'id');
        // Filter roles generally available, but specific assignment happens in store
        $roles = Role::where('name', '!=', 'Super Admin')->groupBy('name')->pluck('name', 'name'); 
        return view('head_officers.create', compact('institutes', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'institute_ids' => 'array',
            'phone' => 'required', // Phone is critical for SMS
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => 2, // Head Officer Type
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->is_active ?? 1
        ];

        if ($request->hasFile('profile_picture')) {
            $userData['profile_picture'] = $request->file('profile_picture')->store('head_officers', 'public');
        }

        $user = User::create($userData);
        
        $assignedInstitutes = $request->input('institute_ids', []);

        // 1. Set Default Institute ID (First one)
        if (count($assignedInstitutes) > 0) {
            $user->update(['institute_id' => $assignedInstitutes[0]]);
        }
        
        // 2. Sync Pivot Table
        $user->institutes()->sync($assignedInstitutes);

        // 3. Assign Institution-Specific Roles
        // We find the 'Head Officer' role for EACH assigned institution
        $rolesToAssign = Role::whereIn('institution_id', $assignedInstitutes)
                             ->where('name', RoleEnum::HEAD_OFFICER->value)
                             ->get();
        
        $user->syncRoles($rolesToAssign);

        // 4. Notify User
        $this->notificationService->sendHeadOfficerCredentials($user, $request->password, $assignedInstitutes);

        return response()->json(['redirect' => route('header-officers.index'), 'message' => __('head_officers.messages.success_create')]);
    }

    public function show($id)
    {
        $head_officer = User::with(['institutes', 'roles'])->findOrFail($id);
        return view('head_officers.show', compact('head_officer'));
    }

    public function edit($id)
    {
        $head_officer = User::with(['institutes', 'roles'])->findOrFail($id);
        $institutes = Institution::where('is_active', true)->pluck('name', 'id');
        $assignedIds = $head_officer->institutes->pluck('id')->toArray();
        // Just showing distinct role names for UI, actual sync is logic-based
        $roles = Role::where('name', '!=', 'Super Admin')->groupBy('name')->pluck('name', 'name');

        return view('head_officers.edit', compact('head_officer', 'institutes', 'assignedIds', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'institute_ids' => 'array',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $updateData = $request->only('name', 'email', 'phone', 'address', 'is_active');
        
        if($request->filled('password')){
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $updateData['profile_picture'] = $request->file('profile_picture')->store('head_officers', 'public');
        }

        $user->update($updateData);
        
        $assignedInstitutes = $request->input('institute_ids', []);
        
        // 1. Sync Institutes
        $user->institutes()->sync($assignedInstitutes);

        // 2. Sync Roles (Re-calculate based on assigned institutes)
        $rolesToAssign = Role::whereIn('institution_id', $assignedInstitutes)
                             ->where('name', RoleEnum::HEAD_OFFICER->value)
                             ->get();

        $user->syncRoles($rolesToAssign);

        // 3. Notify User (Credentials Updated)
        // Sending notification even on update to ensure they have latest access info
        $this->notificationService->sendHeadOfficerCredentials($user, $request->password, $assignedInstitutes);

        return response()->json(['redirect' => route('header-officers.index'), 'message' => __('head_officers.messages.success_update')]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }
        $user->institutes()->detach();
        // Remove roles before deleting to be clean, though cascade usually handles it
        $user->roles()->detach();
        $user->delete();
        return response()->json(['message' => __('head_officers.messages.success_delete')]);
    }
}