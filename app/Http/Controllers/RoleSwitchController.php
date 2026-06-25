<?php

namespace App\Http\Controllers;

use App\Services\ActiveRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSwitchController extends BaseController
{
    public function __construct(private ActiveRoleService $activeRoles)
    {
        $this->middleware('auth');
    }

    public function switch(Request $request)
    {
        $request->validate([
            'role' => 'required|string|max:100',
        ]);

        $user = Auth::user();
        $this->activeRoles->setActiveRole($user, $request->role);

        return redirect()
            ->route('dashboard')
            ->with('success', __('role.switched_to', ['role' => $request->role]));
    }
}
