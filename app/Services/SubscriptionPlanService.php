<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use App\Models\Package;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Applies subscription / plan changes when upgrades are approved.
 */
class SubscriptionPlanService
{
    public function __construct(protected PlanContextService $planContext) {}

    /**
     * Apply an approved upgrade request to the school's active subscription.
     *
     * @return array{applied: bool, package: ?Package, message: ?string}
     */
    public function applyApprovedUpgrade(PlanUpgradeRequest $request): array
    {
        $request->load(['requestedPackage', 'currentPackage']);

        if (!$request->institution_id) {
            return ['applied' => false, 'package' => null, 'message' => 'missing_institution'];
        }

        return DB::transaction(function () use ($request) {
            $subscription = Subscription::where('institution_id', $request->institution_id)
                ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending_payment' THEN 1 WHEN 'expired' THEN 2 ELSE 3 END")
                ->orderByDesc('end_date')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$subscription) {
                return ['applied' => false, 'package' => null, 'message' => 'no_subscription'];
            }

            $subscription->load('package');
            $currentPackage = $subscription->package ?? $request->currentPackage;
            $targetPackage  = $this->resolveTargetPackage($request, $currentPackage);

            if (!$targetPackage) {
                return ['applied' => false, 'package' => null, 'message' => 'no_target_package'];
            }

            $updates = [
                'package_id' => $targetPackage->id,
                'price_paid' => $targetPackage->price,
            ];

            if ($subscription->status !== 'cancelled') {
                $updates['status'] = 'active';
            }

            $subscription->update($updates);

            $this->syncInstitutionFromPackage($request->institution_id, $targetPackage);
            $this->ensurePackageAiFlags($targetPackage);

            // Persist requested package on the request record for audit trail
            if (!$request->requested_package_id) {
                $request->update(['requested_package_id' => $targetPackage->id]);
            }

            return [
                'applied'  => true,
                'package'  => $targetPackage->fresh(),
                'message'  => null,
            ];
        });
    }

    public function syncInstitutionFromPackage(int $institutionId, Package $package): void
    {
        InstitutionSetting::set(
            $institutionId,
            'enabled_modules',
            json_encode($package->modules ?? []),
            'modules'
        );

        // Enable AI perks when the target plan qualifies
        if ($this->planContext->isProPackage($package)) {
            InstitutionSetting::set($institutionId, 'ai_enabled', '1', 'ai');
            InstitutionSetting::set($institutionId, 'ai_unlimited', '1', 'ai');
        } elseif ($this->planContext->packageIncludesAi($package, $institutionId)) {
            InstitutionSetting::set($institutionId, 'ai_enabled', '1', 'ai');
        }
    }

    protected function resolveTargetPackage(PlanUpgradeRequest $request, ?Package $current): ?Package
    {
        if ($request->requestedPackage) {
            return $request->requestedPackage;
        }

        // "Any higher plan" — pick the next tier above current price
        if ($current) {
            $higher = Package::where('is_active', true)
                ->where('price', '>', $current->price)
                ->orderBy('price')
                ->first();

            if ($higher) {
                return $higher;
            }

            // Already on the highest tier: keep current plan but grant pro/AI perks
            return $current;
        }

        return Package::where('is_active', true)->orderByDesc('price')->first();
    }

    protected function ensurePackageAiFlags(Package $package): void
    {
        if (!$this->planContext->isProPackage($package)) {
            return;
        }
        $updates = [];
        if (Schema::hasColumn('packages', 'ai_enabled') && !$package->ai_enabled) {
            $updates['ai_enabled'] = true;
        }
        if (Schema::hasColumn('packages', 'ai_unlimited') && !$package->ai_unlimited) {
            $updates['ai_unlimited'] = true;
        }
        if (!empty($updates)) {
            $package->update($updates);
        }
    }
}
