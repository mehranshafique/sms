<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('profile.page_title'));
    }

    /**
     * Display the user's profile.
     */
    public function index()
    {
        $user = Auth::user();
        // Load relationships if they exist (e.g., student or staff profile)
        $user->load(['student', 'staff', 'roles']);
        
        return view('profile.index', compact('user'));
    }

    /**
     * Update the user's basic profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ], [
            'profile_picture.max' => 'The profile picture must not be greater than 2MB.',
            'username.unique' => 'This username is already taken.',
        ]);

        // Handle Profile Picture Upload
        if ($request->hasFile('profile_picture')) {
            // Delete old image if exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new image
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->profile_picture = $path;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->username = $request->username;
        $user->phone = $request->phone; 
        $user->address = $request->address; 
        
        // If user is also a Student or Staff, sync data optionally
        if($user->student) {
            $user->student->update([
                'first_name' => explode(' ', $request->name)[0], // Simple split, might want refinement
                'email' => $request->email,
                'mobile_number' => $request->phone
            ]);
        }
        if($user->staff) {
            $user->staff->update([
                'email' => $request->email,
                'phone' => $request->phone
            ]);
        }

        $user->save();

        return redirect()->route('profile.index')->with('success', __('profile.update_success'));
    }

    /**
     * Update the user's password securely.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile.index')->with('success', __('profile.password_update_success'));
    }
}