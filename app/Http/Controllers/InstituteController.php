<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\IdGeneratorService;
use App\Enums\UserType;
use App\Enums\RoleEnum;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InstituteController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->authorizeResource(Institution::class, 'institute');
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Institution::select('*');
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
                ->addColumn('logo', function($row){
                    $url = $row->logo ? asset('storage/'.$row->logo) : asset('images/no-image.png');
                    return '<img src="'.$url.'" class="rounded-circle" width="35" alt="">';
                })
                ->addColumn('contact', function($row){
                    return '<div class="d-flex flex-column">
                                <span class="fs-14 fw-bold">'.$row->acronym.'</span>
                                <span class="fs-12 text-muted">'.$row->phone.'</span>
                            </div>';
                })
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('institute.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('institute.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('view', $row)){
                         $btn .= '<a href="'.route('institutes.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    }

                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('institutes.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('institute.edit').'">
                                    <i class="fa fa-pencil"></i>
                                </a>';
                    }

                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" title="'.__('institute.delete').'">
                                    <i class="fa fa-trash"></i>
                                </button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'logo', 'contact', 'is_active', 'action'])
                ->make(true);
        }

        $totalInstitutes = Institution::count();
        $activeInstitutes = Institution::where('is_active', true)->count();
        $inactiveInstitutes = Institution::where('is_active', false)->count();
        $newInstitutes = Institution::where('created_at', '>=', now()->subMonth())->count();

        return view('institutions.index', compact('totalInstitutes', 'activeInstitutes', 'inactiveInstitutes', 'newInstitutes'));
    }

    public function create()
    {
        return view('institutions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'acronym' => 'nullable|string|max:50',
            'type' => 'required|in:primary,secondary,university,mixed',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'commune' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+\d+/'], 
            'email' => 'nullable|email',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
        ], [
            'phone.regex' => __('institute.phone_format_error'),
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('institutes', 'public');
        }

        $validated['code'] = IdGeneratorService::generateInstitutionCode($request->city, $request->commune);

        DB::transaction(function () use ($validated, $request) {
            $institute = Institution::create($validated);

            // 1. Create Essential Roles for this Institution
            $headOfficerRole = Role::firstOrCreate([
                'name' => RoleEnum::HEAD_OFFICER->value,
                'guard_name' => 'web',
                'institution_id' => $institute->id
            ]);

            Role::firstOrCreate([
                'name' => RoleEnum::TEACHER->value,
                'guard_name' => 'web',
                'institution_id' => $institute->id
            ]);

            Role::firstOrCreate([
                'name' => RoleEnum::STUDENT->value,
                'guard_name' => 'web',
                'institution_id' => $institute->id
            ]);

            // 2. Create Admin User
            if($request->filled('email') && $request->filled('password')) {
                $adminUser = User::create([
                    'name' => 'Admin ' . ($request->acronym ?? $institute->name),
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'institute_id' => $institute->id,
                    'user_type' => UserType::HEAD_OFFICER->value,
                    'mobile_number' => $request->phone,
                ]);

                // 3. Assign Head Officer Role to User
                $adminUser->assignRole($headOfficerRole);

                $this->notificationService->sendInstitutionCreation($institute, $adminUser, $request->password);
            }
        });

        return response()->json(['message' => __('institute.messages.success_create'), 'redirect' => route('institutes.index')]);
    }

    public function show(Institution $institute)
    {
        return view('institutions.show', compact('institute'));
    }

    public function edit(Institution $institute)
    {
        return view('institutions.edit', compact('institute'));
    }

    public function update(Request $request, Institution $institute)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'acronym' => 'nullable|string|max:50',
            'type' => 'required|in:primary,secondary,university,mixed',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'commune' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+\d+/'],
            'email' => 'nullable|email',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
        ], [
            'phone.regex' => __('institute.phone_format_error'),
        ]);

        if ($request->hasFile('logo')) {
            if ($institute->logo) {
                Storage::disk('public')->delete($institute->logo);
            }
            $validated['logo'] = $request->file('logo')->store('institutes', 'public');
        }

        DB::transaction(function () use ($validated, $request, $institute) {
            $institute->update($validated);

            // 1. Ensure Roles exist on Update (Self-Healing)
            $roles = [RoleEnum::HEAD_OFFICER->value, RoleEnum::TEACHER->value, RoleEnum::STUDENT->value];
            foreach ($roles as $roleName) {
                Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'institution_id' => $institute->id
                ]);
            }

            // 2. Update Admin User Logic
            if($request->filled('password') && $request->filled('email')) {
                $admin = User::where('email', $request->email)->first();
                if($admin) {
                    $admin->update(['password' => Hash::make($request->password)]);
                    
                    // Ensure Head Officer Role assignment
                    $headOfficerRole = Role::where('name', RoleEnum::HEAD_OFFICER->value)
                        ->where('institution_id', $institute->id)
                        ->first();
                        
                    if($headOfficerRole && !$admin->hasRole($headOfficerRole)) {
                        $admin->assignRole($headOfficerRole);
                    }
                }
            }
        });

        return response()->json(['message' => __('institute.messages.success_update'), 'redirect' => route('institutes.index')]);
    }

    public function destroy(Institution $institute)
    {
        if ($institute->logo) {
            Storage::disk('public')->delete($institute->logo);
        }
        $institute->delete();
        return response()->json(['message' => __('institute.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Institution::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            $institutes = Institution::whereIn('id', $ids)->get();
            foreach ($institutes as $institute) {
                if ($institute->logo) {
                    Storage::disk('public')->delete($institute->logo);
                }
                $institute->delete();
            }
            return response()->json(['success' => __('institute.messages.success_delete')]);
        }
        return response()->json(['error' => __('institute.something_went_wrong')]);
    }
}