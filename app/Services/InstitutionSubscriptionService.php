<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Package;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InstitutionSubscriptionService
{
    public function __construct(protected SubscriptionPlanService $planService)
    {
    }

    /**
     * Assign the first subscription when a school is onboarded.
     * Super Admin may pass a package_id; otherwise the configured default plan is used.
     */
    public function assignInitialPlan(Institution $institution, ?int $packageId = null): ?Subscription
    {
        $package = $this->resolvePackage($packageId);
        if (!$package) {
            Log::warning('Institution created without subscription: no active package found.', [
                'institution_id' => $institution->id,
            ]);

            return null;
        }

        $startDate = now()->startOfDay();
        $durationDays = (int) ($package->duration_days ?: config('subscription.initial_duration_days', 365));
        $endDate = $startDate->copy()->addDays($durationDays);

        $subscription = Subscription::create([
            'institution_id' => $institution->id,
            'package_id' => $package->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => config('subscription.initial_status', 'active'),
            'price_paid' => $package->price,
            'payment_method' => 'onboarding',
            'notes' => 'Auto-assigned on institution creation.',
        ]);

        $this->planService->syncInstitutionFromPackage($institution->id, $package);

        return $subscription;
    }

    public function resolvePackage(?int $packageId = null): ?Package
    {
        if ($packageId) {
            $selected = Package::where('is_active', true)->find($packageId);
            if ($selected) {
                return $selected;
            }
        }

        $configuredId = config('subscription.default_package_id');
        if ($configuredId) {
            $byId = Package::where('is_active', true)->find($configuredId);
            if ($byId) {
                return $byId;
            }
        }

        $configuredName = config('subscription.default_package_name', 'Basic Plan');
        if ($configuredName) {
            $byName = Package::where('is_active', true)
                ->where('name', $configuredName)
                ->first();
            if ($byName) {
                return $byName;
            }
        }

        return Package::where('is_active', true)->orderBy('price')->orderBy('id')->first();
    }

    /** @return \Illuminate\Support\Collection<int, Package> */
    public function selectablePackages()
    {
        return Package::where('is_active', true)->orderBy('price')->orderBy('name')->get();
    }

    public function defaultPackageId(): ?int
    {
        return $this->resolvePackage()?->id;
    }
}
