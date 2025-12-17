<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User; // Added User model
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash; // Added Hash

class InstituteController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Institution::class, 'institute');
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
                    // Ensure the 'public' disk is linked: php artisan storage:link
                    $url = $row->logo ? asset('storage/'.$row->logo) : asset('images/no-image.png');
                    return '<img src="'.$url.'" class="rounded-circle" width="35" alt="">';
                })
                ->addColumn('contact', function($row){ // FIXED: Added missing 'contact' column
                    return '<div class="d-flex flex-column">
                                <span class="fs-14">'.$row->phone.'</span>
                                <span class="fs-12 text-muted">'.$row->email.'</span>
                            </div>';
                })
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('institute.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('institute.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    // Show Button
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
                ->rawColumns(['checkbox', 'logo', 'contact', 'is_active', 'action']) // Added 'contact'
                ->make(true);
        }

        // Stats Logic
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
            'code' => 'required|string|max:30|unique:institutions,code',
            'type' => 'required|in:primary,secondary,university,mixed',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6', // Validate password if provided for admin creation
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('institutes', 'public');
        }

        $institute = Institution::create($validated);

        // Optional: Create an Admin User for this Institute
        if($request->filled('email') && $request->filled('password')) {
            User::create([
                'name' => 'Admin ' . $institute->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'institute_id' => $institute->id, // Assuming direct link exists or pivot needed
                'user_type' => 3, // Branch/Institute Admin
            ]);
        }

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
            'code' => 'required|string|max:30|unique:institutions,code,'.$institute->id,
            'type' => 'required|in:primary,secondary,university,mixed',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6', // Optional password update
        ]);

        if ($request->hasFile('logo')) {
            if ($institute->logo) {
                Storage::disk('public')->delete($institute->logo);
            }
            $validated['logo'] = $request->file('logo')->store('institutes', 'public');
        }

        $institute->update($validated);

        // Update Admin Password if provided (Assumes we find the admin by email)
        if($request->filled('password') && $request->filled('email')) {
            $admin = User::where('email', $request->email)->first();
            if($admin) {
                $admin->update(['password' => Hash::make($request->password)]);
            }
        }

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