<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class InstituteController extends BaseController
{
    public function index()
    {
        $this->setPageTitle('Institutes');
        $institutes = Institute::latest()->paginate(10);
        return view('institutes.index', compact('institutes'));
    }

    public function create()
    {
        $this->setPageTitle('Add Institute');
        return view('institutes.create');
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'plan_password' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:institutes',
            'code' => 'required|string|max:30|unique:institutes,code',
            'type' => 'required|in:primary,secondary,university,mixed',
            'phone' => 'required|string|max:30',
        ]);

        // ❌ Validation Failed — Return JSON for AJAX
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create institute
        $institute = new Institute();
        $institute->name = $request->name;
        $institute->email = $request->email;
        $institute->code = $request->code;
        $institute->type = $request->type;
        $institute->phone = $request->phone;
        $institute->plan_password = $request->plan_password;
        $institute->city = $request->city;
        $institute->address = $request->address;
        $institute->country = $request->country;
        $institute->save();

        // Create admin user
        $adminData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->plan_password),
            'institute_id' => $institute->id,
        ];

        $user = User::create($adminData);
        $user->assignRole('Admin');
        // ✅ Return JSON success for AJAX
        return response()->json([
            'status' => 'success',
            'message' => 'Institute created successfully.',
            'redirect' => route('institutes.index')
        ], 200);
    }

    public function edit(Institute $institute)
    {
        $this->setPageTitle('Edit Institute');
        return view('institutes.edit', compact('institute'));
    }

//    public function update(Request $request, Institute $institute)
//    {
//        $request->validate([
//            'name' => 'required|string|max:150',
//            'email' => 'required|string|email|max:150',
//            'code' => 'required|string|max:30|unique:institutes,code,' . $institute->id,
//            'type' => 'required|in:primary,secondary,university,mixed',
//            'phone' => 'required|string|max:30',
//        ]);
//
//        $institute->update($request->all());
//        $adminUser = User::where('institute_id', $institute->id)->first();
//        $adminData = [
//            'name' => $request->name,
//            'email' => $request->email,
//        ];
//
//        if($request->plan_password){
//            $adminData['password'] = Hash::make($request->password);
//        }
//
//        $adminUser = User::where('institute_id', $institute->id)->first();
//
//        if ($adminUser) {
//            $adminUser->update($adminData);
//        } else {
//            $adminData['institute_id'] = $institute->id;
//            $adminUser = User::create($adminData);
//        }
//
//
//        return redirect()->route('institutes.index')->with('success', 'Institute updated successfully.');
//    }

    public function update(Request $request, Institute $institute)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:institutes,email,' . $institute->id,
            'code' => 'required|string|max:30|unique:institutes,code,' . $institute->id,
            'type' => 'required|in:primary,secondary,university,mixed',
            'phone' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $institute->update($request->only(['name','email','code','type','phone','city','country','address','is_active']));

        $adminUser = User::where('institute_id', $institute->id)->first();

        $adminData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->plan_password) {
            $adminData['password'] = Hash::make($request->plan_password);
        }

        if ($adminUser) {
            $adminUser->update($adminData);
        } else {
            $adminData['institute_id'] = $institute->id;
            User::create($adminData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Institute updated successfully.',
            'redirect' => route('institutes.index')
        ], 200);
    }

    public function destroy(Institute $institute)
    {
        User::where('institute_id',$institute->id)->delete();
        $institute->delete();
        return redirect()->route('institutes.index')->with('success', 'Institute deleted successfully.');
    }
}
