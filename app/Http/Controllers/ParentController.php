<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StudentParent;
use App\Models\Institution;
use App\Enums\UserType;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ParentController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->authorizeResource(StudentParent::class, 'student_parent');
        $this->middleware(PermissionMiddleware::class . ':student_parent.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':student_parent.create')->only(['create', 'store']);
        $this->middleware(PermissionMiddleware::class . ':student_parent.update')->only(['edit', 'update']);
        $this->middleware(PermissionMiddleware::class . ':student_parent.delete')->only(['destroy', 'bulkDelete']);
        $this->setPageTitle(__('parent.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = StudentParent::with(['user', 'students'])
                ->select('parents.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function($row){
                    // Priority: Guardian > Father > Mother
                    $name = $row->guardian_name ?? $row->father_name ?? $row->mother_name ?? 'N/A';
                    return '<a href="'.route('parents.show', $row->id).'" class="fw-bold text-primary">'.$name.'</a>';
                })
                ->addColumn('phones', function($row){
                    $phones = [];
                    if($row->father_phone) $phones[] = '<i class="fa fa-male me-1 text-muted"></i> '.$row->father_phone;
                    if($row->mother_phone) $phones[] = '<i class="fa fa-female me-1 text-muted"></i> '.$row->mother_phone;
                    if($row->guardian_phone) $phones[] = '<i class="fa fa-user me-1 text-muted"></i> '.$row->guardian_phone;
                    return implode('<br>', $phones);
                })
                ->addColumn('email', function($row){
                    return $row->guardian_email ?? $row->user->email ?? '-';
                })
                ->addColumn('wards', function($row){
                    return '<span class="badge badge-info">'.$row->students->count().' Students</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    $btn .= '<a href="'.route('parents.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('parents.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['name', 'phones', 'wards', 'action'])
                ->make(true);
        }

        return view('parents.index');
    }

    public function create()
    {
        return view('parents.create');
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $request->validate([
            'guardian_name' => 'nullable|required_without_all:father_name,mother_name|string|max:100',
            'guardian_phone' => 'nullable|required_without_all:father_phone,mother_phone|string|max:20',
            'guardian_email' => 'nullable|email|unique:users,email',
        ]);

        DB::transaction(function () use ($request, $institutionId) {
            
            // 1. Create User Account if Email Provided
            $userId = null;
            if ($request->guardian_email) {
                $password = 'Parent123!'; // Default, should notify
                $shortcode = 'PAR-' . rand(10000, 99999);
                
                $user = User::create([
                    'name' => $request->guardian_name ?? $request->father_name ?? 'Parent',
                    'email' => $request->guardian_email,
                    'password' => Hash::make($password),
                    'user_type' => UserType::GUARDIAN->value,
                    'institute_id' => $institutionId,
                    'username' => $shortcode,
                    'shortcode' => $shortcode,
                    'phone' => $request->guardian_phone ?? $request->father_phone,
                    'is_active' => true,
                ]);
                $user->assignRole(RoleEnum::GUARDIAN->value);
                $userId = $user->id;
            }

            // 2. Create Parent Record
            StudentParent::create([
                'institution_id' => $institutionId,
                'user_id' => $userId,
                'father_name' => $request->father_name,
                'father_phone' => $request->father_phone,
                'father_occupation' => $request->father_occupation,
                'mother_name' => $request->mother_name,
                'mother_phone' => $request->mother_phone,
                'mother_occupation' => $request->mother_occupation,
                'guardian_name' => $request->guardian_name,
                'guardian_phone' => $request->guardian_phone,
                'guardian_email' => $request->guardian_email,
                'guardian_relation' => $request->guardian_relation,
                'family_address' => $request->family_address,
            ]);
        });

        return redirect()->route('parents.index')->with('success', __('parent.success_create'));
    }

    public function show(StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);
        
        $parent->load(['students.classSection.gradeLevel', 'user']);
        return view('parents.show', compact('parent'));
    }

    public function edit(StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);

        return view('parents.edit', compact('parent'));
    }

    public function update(Request $request, StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);

        $request->validate([
            'guardian_email' => 'nullable|email|unique:users,email,' . $parent->user_id,
        ]);

        $parent->update($request->except(['_token', '_method']));

        // Update User Email if linked
        if ($parent->user_id && $request->guardian_email) {
            $user = User::find($parent->user_id);
            if($user) $user->update(['email' => $request->guardian_email]);
        }

        return redirect()->route('parents.index')->with('success', __('parent.success_update'));
    }

    public function destroy(StudentParent $parent)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $parent->institution_id != $institutionId) abort(403);

        if ($parent->students()->count() > 0) {
            return response()->json(['message' => __('parent.cannot_delete')], 422);
        }

        if ($parent->user_id) {
            User::destroy($parent->user_id);
        }
        
        $parent->delete();

        return response()->json(['message' => __('parent.success_delete')]);
    }

    // Keep existing 'check' method
    /**
     * AJAX: Check if a parent exists by phone number.
     * Checks both the StudentParent (parents) table and the User table.
     */
    public function check(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:6',
        ]);

        $phone = $request->phone;

        // 1. Check 'parents' table (StudentParent model)
        // We look for the phone number in father, mother, or guardian fields
        $studentParent = StudentParent::where(function($query) use ($phone) {
            $query->where('father_phone', $phone)
                  ->orWhere('mother_phone', $phone)
                  ->orWhere('guardian_phone', $phone);
        })->first();

        if ($studentParent) {
            // Determine the most relevant name to display
            $displayName = $studentParent->guardian_name ?? $studentParent->father_name ?? $studentParent->mother_name ?? 'Parent';
            
            // Try to be specific if we know which phone matched
            if ($studentParent->father_phone == $phone && !empty($studentParent->father_name)) {
                $displayName = $studentParent->father_name;
            } elseif ($studentParent->mother_phone == $phone && !empty($studentParent->mother_name)) {
                $displayName = $studentParent->mother_name;
            } elseif ($studentParent->guardian_phone == $phone && !empty($studentParent->guardian_name)) {
                $displayName = $studentParent->guardian_name;
            }

            return response()->json([
                'exists' => true,
                'source' => 'parent_record',
                'id' => $studentParent->id,           // ID from parents table
                'parent_id' => $studentParent->id,    // Explicit parent ID
                'user_id' => $studentParent->user_id, // Linked User ID (if any)
                'name' => $displayName,
                'email' => $studentParent->guardian_email,
                
                // Return all specific fields for auto-filling the form
                'father_name' => $studentParent->father_name,
                'father_phone' => $studentParent->father_phone,
                'mother_name' => $studentParent->mother_name,
                'mother_phone' => $studentParent->mother_phone,
                'guardian_name' => $studentParent->guardian_name,
                'guardian_email' => $studentParent->guardian_email,
                'guardian_phone' => $studentParent->guardian_phone,
            ]);
        }

        // 2. Check 'users' table (Fallback)
        $userParent = User::where('phone', $phone)
            ->where('user_type', UserType::GUARDIAN->value)
            ->first();

        if ($userParent) {
            return response()->json([
                'exists' => true,
                'source' => 'user_account',
                'id' => $userParent->id,         // ID from users table
                'parent_id' => null,             // No parent record yet
                'user_id' => $userParent->id,    // Explicit User ID
                'name' => $userParent->name,
                'email' => $userParent->email,
                // Map generic user info to guardian fields as fallback
                'guardian_name' => $userParent->name,
                'guardian_email' => $userParent->email,
                'guardian_phone' => $userParent->phone,
            ]);
        }

        return response()->json(['exists' => false]);
    }
}