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
use Illuminate\Support\Str;

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
        $rules = [
            'name'      => 'required|string|max:150',
            'acronym'   => 'nullable|string|max:20',
            'type'      => 'required|in:primary,secondary,university,mixed',
            'country'   => 'required|exists:countries,id',
            'state'     => 'required|exists:states,id',
            'city'      => 'required|exists:cities,id',
            'address'   => 'nullable|string',
            'full_phone'=> 'required|string|max:30',
            // Check unique in institutions AND users table
            'email'     => ['required', 'email', 'unique:institutions,email', 'unique:users,email'],
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'password'  => 'required|string|min:6', // REQUIRED for creation
        ];

        $messages = [
            'name.required'       => __('institute.validation_name_required'),
            'type.required'       => __('institute.validation_type_required'),
            'country.required'    => __('locations.country_required'),
            'state.required'      => __('locations.state_required'),
            'city.required'       => __('locations.city_required'),
            'full_phone.required' => __('institute.validation_phone_required'),
            'email.unique'        => __('institute.validation_email_unique'), 
        ];

        $validated = $request->validate($rules, $messages);

        // --- Capitalize Text Fields ---
        $textFields = ['name', 'acronym', 'address'];
        
        // Exclude 'password' so it isn't passed to Institution::fill()
        $data = $request->except(['logo', 'password', 'full_phone', 'commune']); 
        
        foreach ($textFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = ucwords(strtolower($data[$field]));
            }
        }

        DB::transaction(function () use ($validated, $request, $data) {
            $institute = new Institution();
            
            // $data already excludes 'password', so this won't throw SQL error
            $institute->fill($data);
            
            // To be safe and maintain "meaningless" structure (1-5-001) as requested
            $institute->code = IdGeneratorService::generateInstitutionCode($request->state, $request->city);
            
            $institute->phone = $request->full_phone;

            // Note: Password is NOT stored on Institution model, only on User model.
            // We removed $institute->password assignment here.

            if ($request->hasFile('logo')) {
                $institute->logo = $request->file('logo')->store('institutes', 'public');
            }

            $institute->save();
            $this->createInstituteRoles($institute);

            if($request->filled('email') && $request->filled('password')) {
                // Use capitalized Name/Acronym for admin name
                $adminName = __('institute.admin_default_name', ['name' => ($data['acronym'] ?? $data['name'])]);
                
                $adminUser = User::create([
                    'name'          => $adminName,
                    'email'         => $request->email,
                    'password'      => Hash::make($request->password),
                    'institute_id'  => $institute->id,
                    'user_type'     => UserType::HEAD_OFFICER->value,
                    'mobile_number' => $request->full_phone,
                ]);

                $headOfficerRole = Role::where('name', RoleEnum::HEAD_OFFICER->value)
                                       ->where('institution_id', $institute->id)
                                       ->first();

                if ($headOfficerRole) {
                    $adminUser->assignRole($headOfficerRole);
                }

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
        $adminUser = User::where('institute_id', $institute->id)
                         ->where('user_type', UserType::HEAD_OFFICER->value)
                         ->first();
        $adminUserId = $adminUser ? $adminUser->id : null;

        $rules = [
            'name'      => 'required|string|max:150',
            'acronym'   => 'nullable|string|max:20',
            'type'      => 'required|in:primary,secondary,university,mixed',
            'country'   => 'required|exists:countries,id',
            'state'     => 'required|exists:states,id',
            'city'      => 'required|exists:cities,id',
            'address'   => 'nullable|string',
            'full_phone'=> 'required|string|max:30',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'password'  => 'nullable|string|min:6', // Optional for update
            'email'     => [
                'required', 
                'email', 
                // Fix: Ensure correct table name 'institutions'
                Rule::unique('institutions')->ignore($institute->id),
                Rule::unique('users')->ignore($adminUserId)
            ],
        ];

        $messages = [
            'name.required'       => __('institute.validation_name_required'),
            'type.required'       => __('institute.validation_type_required'),
            'country.required'    => __('locations.country_required'),
            'state.required'      => __('locations.state_required'),
            'city.required'       => __('locations.city_required'),
            'full_phone.required' => __('institute.validation_phone_required'),
        ];

        $validated = $request->validate($rules, $messages);

        // --- Capitalize Text Fields ---
        $textFields = ['name', 'acronym', 'address'];
        
        // Exclude 'password' so it isn't passed to Institution::fill()
        $data = $request->except(['logo', 'password', 'full_phone', 'commune']);

        foreach ($textFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = ucwords(strtolower($data[$field]));
            }
        }

        if ($request->hasFile('logo')) {
            if ($institute->logo) {
                Storage::disk('public')->delete($institute->logo);
            }
            $data['logo'] = $request->file('logo')->store('institutes', 'public');
        }

        $data['phone'] = $request->full_phone;

        DB::transaction(function () use ($validated, $request, $institute, $adminUser, $data) {
            
            // Password is NOT stored on Institution model, do NOT add it to $data
            // If it was in $data, remove it:
            if(isset($data['password'])) unset($data['password']);

            $institute->update($data);
            $this->createInstituteRoles($institute);

            if($request->filled('email')) {
                if($adminUser) {
                    $updateData = ['email' => $request->email];
                    
                    // Only update User password if provided
                    if ($request->filled('password')) {
                        $updateData['password'] = Hash::make($request->password);
                    }
                    
                    $adminUser->update($updateData);

                    $headOfficerRole = Role::where('name', RoleEnum::HEAD_OFFICER->value)
                        ->where('institution_id', $institute->id)
                        ->first();
                    if($headOfficerRole && !$adminUser->hasRole($headOfficerRole)) {
                        $adminUser->assignRole($headOfficerRole);
                    }

                    if ($request->filled('password')) {
                        $this->notificationService->sendInstitutionCreation($institute, $adminUser, $request->password);
                    }
                }
            }
        });

        return response()->json(['message' => __('institute.messages.success_update'), 'redirect' => route('institutes.index')]);
    }

    public function destroy(Institution $institute)
    {
        DB::transaction(function () use ($institute) {
            User::where('institute_id', $institute->id)->delete();
            
            if ($institute->logo) {
                Storage::disk('public')->delete($institute->logo);
            }
            $institute->delete();
        });

        return response()->json(['message' => __('institute.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Institution::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            $institutes = Institution::whereIn('id', $ids)->get();
            
            DB::transaction(function () use ($institutes) {
                foreach ($institutes as $institute) {
                    User::where('institute_id', $institute->id)->delete();

                    if ($institute->logo) {
                        Storage::disk('public')->delete($institute->logo);
                    }
                    $institute->delete();
                }
            });
            
            return response()->json(['success' => __('institute.messages.success_delete')]);
        }
        return response()->json(['error' => __('institute.something_went_wrong')]);
    }

    private function createInstituteRoles($institute)
    {
        $roles = [RoleEnum::HEAD_OFFICER->value, RoleEnum::TEACHER->value, RoleEnum::STUDENT->value];
        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'institution_id' => $institute->id
            ]);
        }
    }
}