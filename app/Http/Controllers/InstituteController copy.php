<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
// use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // ✅ 1. Import Trait

class InstituteController extends BaseController
{
    public function __construct()
    {
        // ✅ 3. This one line now handles ALL standard permissions securely
        $this->authorizeResource(Institute::class, 'institute');
        
        $this->setPageTitle(__('institute.page_title'));
    }

    /**
     * Display a listing of the resource.
     */
     /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Institute::select('*');

            // Using the Facade ::of() syntax instead of the helper function
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    // Only show checkbox if user can delete
                    if(auth()->user()->can('institute.delete')){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('id_display', function($row){
                    return '<span class="text-muted">#'.$row->code.'</span>';
                })
                ->addColumn('details', function($row){
                    $initial = substr($row->name, 0, 1);
                    return '<div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary-light text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                                    '.$initial.'
                                </div>
                                <div>
                                    <h6 class="fs-16 font-w600 mb-0"><a href="#" class="text-black">'.$row->name.'</a></h6>
                                    <span class="fs-13 text-muted">'.$row->email.'</span>
                                </div>
                            </div>';
                })
                ->editColumn('type', function($row){
                    $badges = [
                        'primary' => 'badge-info',
                        'secondary' => 'badge-secondary',
                        'university' => 'badge-primary',
                        'mixed' => 'badge-warning'
                    ];
                    $badgeClass = $badges[$row->type] ?? 'badge-dark';
                    return '<span class="badge '.$badgeClass.'">'.ucfirst($row->type).'</span>';
                })
                ->addColumn('contact', function($row){
                    return '<div class="d-flex flex-column">
                                <span class="fs-13">'.$row->phone.'</span>
                                <span class="fs-12 text-muted">'.$row->city.'</span>
                            </div>';
                })
                ->editColumn('status', function($row){
                    if($row->is_active){
                        return '<span class="badge light badge-success">
                                    <i class="fa fa-circle text-success me-1"></i> '.trans('institute.active').'
                                </span>';
                    } else {
                        return '<span class="badge light badge-danger">
                                    <i class="fa fa-circle text-danger me-1"></i> '.trans('institute.inactive').'
                                </span>';
                    }
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('institute.edit')){
                        $btn .= '<a href="'.route('institutes.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1" data-bs-toggle="tooltip" title="'.trans('institute.edit').'">
                                    <i class="fa fa-pencil"></i>
                                </a>';
                    }

                    if(auth()->user()->can('institute.delete')){
                        // Using a class for the click event instead of inline form to keep it clean
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'" data-bs-toggle="tooltip" title="'.trans('institute.delete').'">
                                    <i class="fa fa-trash"></i>
                                </button>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'id_display', 'details', 'type', 'contact', 'status', 'action'])
                ->make(true);
        }

        // Stats calculations
        $totalInstitutes = Institute::count();
        $activeInstitutes = Institute::where('is_active', true)->count();
        $inactiveInstitutes = Institute::where('is_active', false)->count();
        $newInstitutes = Institute::where('created_at', '>=', now()->subMonth())->count();

        return view('institutes.index', compact('totalInstitutes', 'activeInstitutes', 'inactiveInstitutes', 'newInstitutes'));
    }
    public function create()
    {
        // No manual authorize() needed -> handled by authorizeResource
        $this->setPageTitle(__('institute.add_institute'));
        return view('institutes.create');
    }

    public function store(Request $request)
    {
        // No manual authorize() needed
        
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'plan_password' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:institutes',
            'type' => 'required|in:primary,secondary,university,mixed',
            'phone' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $institute = new Institute();
        $institute->name = $request->name;
        $institute->email = $request->email;
        $institute->code = mt_rand(100000, 999999);
        $institute->type = $request->type;
        $institute->phone = $request->phone;
        $institute->plan_password = $request->plan_password; // Note: Usually we don't store plain passwords
        $institute->city = $request->city;
        $institute->address = $request->address;
        $institute->country = $request->country;
        $institute->is_active = $request->is_active ?? 1;
        $institute->save();

        // Create admin user
        $adminData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->plan_password),
            'institute_id' => $institute->id,
        ];

        $user = User::create($adminData);
        // Ensure you have Spatie Permission package installed for this line
        if(method_exists($user, 'assignRole')){
             $user->assignRole('Admin');
        }

        return response()->json([
            'status' => 'success',
            'message' => __('institutes.messages.success_create'), // Ensure this key exists
            'redirect' => route('institutes.index')
        ], 200);
    }

    public function edit(Institute $institute)
    {
        $this->setPageTitle(__('institutes.edit_institute'));
        return view('institutes.edit', compact('institute'));
    }

    public function update(Request $request, Institute $institute)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:institutes,email,' . $institute->id,
            'type' => 'required|in:primary,secondary,university,mixed',
            'phone' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $institute->update($request->only(['name','email','type','phone','city','country','address','is_active']));

        // Update linked Admin User
        $adminUser = User::where('institute_id', $institute->id)->first();
        if ($adminUser) {
            $adminUser->name = $request->name;
            $adminUser->email = $request->email;
            if ($request->filled('plan_password')) {
                $adminUser->password = Hash::make($request->plan_password);
            }
            $adminUser->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('institutes.messages.success_update'),
            'redirect' => route('institutes.index')
        ], 200);
    }

    public function destroy(Institute $institute)
    {
        User::where('institute_id', $institute->id)->delete();
        $institute->delete();
        return redirect()->route('institutes.index')->with('success', __('institutes.messages.success_delete'));
    }

    /**
     * Bulk Delete Handler
     * Requires 'deleteAny' in ResourcePolicy
     */
    public function bulkDelete(Request $request)
    {
        // Manually authorize because it's a custom method
        $this->authorize('deleteAny', Institute::class);

        $ids = $request->ids;
        if (!empty($ids)) {
            Institute::whereIn('id', $ids)->delete();
            // Also delete users
            User::whereIn('institute_id', $ids)->delete();
            
            return response()->json(['success' => __('institute.bulk_delete_success')]);
        }

        return response()->json(['error' => __('institute.no_items_selected')], 400);
    }
}