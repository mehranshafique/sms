<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;
use Carbon\Carbon;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // 1. Skip checks for unauthenticated users (let auth middleware handle them)
        // or Super Admins (who always have access)
        if (!$user || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        // 2. Resolve Institution Context
        // CRITICAL FIX: Check Session FIRST, then User's fixed ID.
        // This ensures that if a Head Officer switches to a different school,
        // we check the subscription of the *active* school, not their default one.
        $institutionId = session('active_institution_id') ?: $user->institute_id;

        // FIX: Bypass subscription check if in Global Dashboard mode
        if ($institutionId === 'global') {
            return $next($request);
        }

        if ($institutionId) {
            // 3. Find Active Subscription
            // We look for the latest subscription that is marked as active.
            $subscription = Subscription::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->latest('end_date')
                ->first();

            // 4. No Subscription Found
            if (!$subscription) {
                return response()->view('errors.subscription_expired', [], 403);
            }

            // 5. Check Expiry
            // Use endOfDay() to ensure the user has access until 23:59:59 on the expiry date.
            if ($subscription->end_date->endOfDay()->isPast()) {
                
                // Grace Period Logic (e.g. 3 days extra access)
                $gracePeriodEnd = $subscription->end_date->copy()->addDays(3)->endOfDay();
                
                if (now()->gt($gracePeriodEnd)) {
                    // Grace period over -> Block Access
                    return response()->view('errors.subscription_expired', [], 403);
                } else {
                    // Inside Grace Period -> Allow Access but Flash Warning
                    $daysOver = now()->diffInDays($gracePeriodEnd);
                    session()->flash('warning', 'Your subscription has expired. Access will be revoked in ' . $daysOver . ' days. Please contact support.');
                }
            } else {
                // 6. Subscription Active -> Check for Renewal Warning
                // Warn if expiring within 7 days
                $daysLeft = now()->diffInDays($subscription->end_date, false);
                
                if ($daysLeft <= 7 && $daysLeft >= 0) {
                    session()->flash('warning', "Subscription expiring in {$daysLeft} days. Please renew soon.");
                }
            }
        }

        return $next($request);
    }
}