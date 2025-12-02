<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class HeadOfficersController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle('Head Officers');
    }

    public function index()
    {
        $head_officers = User::where('user_type', 2)->get();
        return view('head_officers.index', compact('head_officers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'address'  => 'required|string',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'user_type' => 2, // Head Officer
            'address'   => $request->address,
            'password'  => Hash::make($request->password),
        ]);
        $user->assignRole('Head Officer');
        return response()->json([
            'status'  => true,
            'message' => 'Head Officer created successfully!',
            'data'    => $user
        ]);
    }

    public function edit($id)
    {
        $officer = User::findOrFail($id);
        return response()->json($officer);
    }

    public function update(Request $request, $id)
    {

        $officer = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email,' . $officer->id,
            'phone'    => 'required|string|max:20',
            'address'  => 'required|string',
            'password' => 'nullable|min:6',
        ]);

        $officer->name    = $request->name;
        $officer->email   = $request->email;
        $officer->phone   = $request->phone;
        $officer->address = $request->address;

        if ($request->password) {
            $officer->password = Hash::make($request->password);
        }

        $officer->save();

        return response()->json([
            'status'  => true,
            'message' => 'Head Officer updated successfully!',
        ]);
    }

    public function destroy($id)
    {
        $officer = User::findOrFail($id);
        $officer->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Head Officer deleted successfully!',
        ]);
    }
}
