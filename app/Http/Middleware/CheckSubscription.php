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
        
        // Skip for Super Admin or non-authenticated
        if (!$user || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Fix: Prioritize Session (Active Context) over Fixed ID (Home School)
        // This allows Head Officers to switch contexts correctly.
        $institutionId = session('active_institution_id') ?: $user->institute_id;

        if ($institutionId) {
            // Find Active Subscription
            $subscription = Subscription::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->latest('end_date')
                ->first();

            if (!$subscription) {
                // No active subscription found -> Redirect to Locked Page
                return response()->view('errors.subscription_expired', [], 403);
            }

            // Check Expiry
            // Fix: Check against END of the day to ensure full day access on expiry date
            if ($subscription->end_date->endOfDay()->isPast()) {
                // Grace Period Logic (e.g. 3 days extra)
                $gracePeriodEnd = $subscription->end_date->copy()->addDays(3)->endOfDay();
                
                if (now()->gt($gracePeriodEnd)) {
                    return response()->view('errors.subscription_expired', [], 403);
                } else {
                    // In Grace Period -> Show Warning Flash Message
                    session()->flash('warning', 'Your subscription has expired. Access will be revoked in ' . now()->diffInDays($gracePeriodEnd) . ' days. Please contact support.');
                }
            } else {
                // Active -> Check for Warning Threshold (e.g. 7 days before)
                $daysLeft = now()->diffInDays($subscription->end_date, false);
                if ($daysLeft <= 7 && $daysLeft >= 0) {
                    session()->flash('warning', "Subscription expiring in {$daysLeft} days. Please renew soon.");
                }
            }
        }

        return $next($request);
    }
}