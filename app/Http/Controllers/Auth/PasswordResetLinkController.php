<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use App\Models\User;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        // 1. Resolve Login Input to Email
        $input = $request->input('login');
        $field = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : null;

        $user = null;

        if ($field === 'email') {
            $user = User::where('email', $input)->first();
        } else {
            // Check Username or Shortcode
            $user = User::where('username', $input)
                  ->orWhere('shortcode', $input)
                  ->first();
        }

        // If user not found, throw generic error to avoid enumeration (or specific if preferred)
        if (! $user) {
            return back()->withInput()->withErrors(['email' => trans('passwords.user')]);
        }

        // 2. Send Password Reset Link using the resolved email
        // We manually merge the 'email' into the request so the broker can use it
        $status = Password::sendResetLink(
            ['email' => $user->email]
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput()->withErrors(['email' => __($status)]);
    }
}