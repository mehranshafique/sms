<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
class StaffController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle('Staff');
    }

    public function index()
    {
        $institute_id = institute()->id; // your helper like institute()
        $staff = Staff::with(['user', 'institute'])
            ->where('institute_id', $institute_id)
            ->get();

        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        $this->setPageTitle('Add Staff');

//        $users = User::doesntHave('staff')->get();

        return view('staff.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            // Normalize campus_id if you use helper like campus()->id
            // 'campus_id' => $request->campus_id ?? campus()->id,
        ]);

        $rules = [
            // Either provide user_id OR provide name+email to create a user
            'name'       => 'required_without:user_id|string|max:255',
            'email'      => 'required_without:user_id|email|max:255|unique:users,email',
            'password'   => 'required|string|min:8', // optional; if not present we'll generate one
            'designation'=> 'nullable|max:100',
            'department' => 'nullable|max:100',
            'hire_date'  => 'nullable|date',
            'status'     => 'required|in:active,on_leave,terminated',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // 1) Resolve or create user
            if ($request->filled('user_id')) {
                $userId = $request->user_id;
            } else {
                // create new user
                $passwordPlain = $request->password ?? Str::random(12); // random if not provided
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'address' => $request->address,
                    'phone'  => $request->phone,
                    'password' => Hash::make($passwordPlain),
                ]);

                // PRODUCTION: don't return plaintext passwords in responses.
                // Instead: queue an email (password reset link or set-password flow).
                // Example: dispatch a job to email the user with a "set password" link.
                // dispatch(new SendNewUserWelcomeEmail($user, $passwordPlain)); // optional

                $userId = $user->id;
            }

            // 2) Generate employee_no unique per campus (use DB locking to avoid race)
            // lockForUpdate requires being inside a transaction and supported by DB (MySQL/InnoDB, Postgres)
            $campusId = institute()->id;

            $last = Staff::where('institute_id', $campusId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $number = $last ? intval(substr($last->employee_no, -4)) + 1 : 1;
            $employee_no = $campusId . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            // 3) Create staff record
            $staff = Staff::create([
                'user_id' => $userId,
                'institute_id' => $campusId,
                'employee_no' => $employee_no,
                'designation' => $request->designation,
                'department' => $request->department,
                'hire_date' => $request->hire_date,
                'status' => $request->status,
            ]);

            // $user = User::find($userId);
            // $user->assignRole('staff'); // ensure role exists

            DB::commit();

            return response()->json([
                'message' => 'Staff created successfully',
                'redirect' => route('staff.index'),
                // Do NOT return passwordPlain in production responses.
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // log detailed error for debugging; keep client response generic
            Log::error('Staff store error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while creating staff. Please try again or contact support.'
            ], 500);
        }
    }


    public function edit(Staff $staff)
    {
        $user = User::find($staff->user_id);

        return view('staff.edit', compact('staff', 'user'));
    }

    public function update(Request $request, Staff $staff)
    {
        $rules = [
            'user_id'    => 'nullable|exists:users,id',
            'name'       => 'required_without:user_id|string|max:255',
            'email'      => 'required_without:user_id|email|max:255|unique:users,email,' . ($staff->user_id ?? 'NULL'),
            'password'   => 'nullable|string|min:8',
            'designation'=> 'nullable|max:100',
            'department' => 'nullable|max:100',
            'hire_date'  => 'nullable|date',
            'status'     => 'required|in:active,on_leave,terminated',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // 1) Update or create user
            if ($request->filled('user_id')) {
                $userId = $request->user_id;
            } else {
                $user = User::find($staff->user_id);

                if ($user) {
                    // Update existing user
                    $user->update([
                        'name' => $request->name,
                        'email' => $request->email,
                        'address' => $request->address,
                        'phone' => $request->phone,
                        'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
                    ]);
                    $userId = $user->id;
                } else {
                    // Create new user if somehow missing
                    $passwordPlain = $request->password ?? Str::random(12);
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'address' => $request->address,
                        'phone' => $request->phone,
                        'password' => Hash::make($passwordPlain),
                    ]);
                    $userId = $user->id;
                }
            }

            // 2) Update staff record
            $staff->update([
                'user_id'    => $userId,
                'designation'=> $request->designation,
                'department' => $request->department,
                'hire_date'  => $request->hire_date,
                'status'     => $request->status,
                'institute_id'  => institute()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Staff updated successfully',
                'redirect' => route('staff.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Staff update error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while updating staff. Please try again or contact support.'
            ], 500);
        }
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        return back()->with('success', 'Staff deleted successfully');
    }
}
