<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use App\Models\Module;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the active subscription/plan for the current user context.
 * Used by the header, AI gating, and the My Plan page — single source of truth.
 */
class PlanContextService
{
    public function resolveInstitutionId(?User $user = null): ?int
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return null;
        }

        $activeId = session('active_institution_id');

        if (($activeId === 'global' || $activeId === 0 || $activeId === '0') && $user->hasRole('Super Admin')) {
            return null;
        }

        if (!empty($activeId) && is_numeric($activeId)) {
            return (int) $activeId;
        }

        return $user->institute_id ? (int) $user->institute_id : null;
    }

    /**
     * Full plan snapshot for views and access checks.
     *
     * @return array{
     *   institution_id: ?int,
     *   subscription: ?Subscription,
     *   package: ?Package,
     *   plan_name: ?string,
     *   is_active: bool,
     *   is_pro: bool,
     *   has_ai: bool,
     *   ai_unlimited: bool,
     *   days_left: ?int,
     * }
     */
    public function snapshot(?User $user = null): array
    {
        $empty = [
            'institution_id'      => null,
            'subscription'        => null,
            'package'             => null,
            'plan_name'           => null,
            'is_active'           => false,
            'is_pro'              => false,
            'includes_ai'         => false,
            'has_ai'              => false,
            'ai_platform_enabled' => $this->isAiPlatformEnabled(),
            'ai_unlimited'        => false,
            'days_left'           => null,
        ];

        $user = $user ?? Auth::user();
        if (!$user) {
            return $empty;
        }

        if ($user->hasRole('Super Admin')) {
            return array_merge($empty, [
                'is_pro'              => true,
                'includes_ai'         => $this->isAiPlatformEnabled(),
                'has_ai'              => $this->isAiPlatformEnabled(),
                'ai_platform_enabled' => $this->isAiPlatformEnabled(),
                'ai_unlimited'        => true,
                'plan_name'           => 'Platform Admin',
                'is_active'           => true,
            ]);
        }

        $institutionId = $this->resolveInstitutionId($user);
        if (!$institutionId) {
            return $empty;
        }

        try {
            $subscription = Subscription::with('package')
                ->where('institution_id', $institutionId)
                ->where('status', 'active')
                ->latest('end_date')
                ->first();

            if (!$subscription || !$subscription->package) {
                return array_merge($empty, ['institution_id' => $institutionId]);
            }

            $package   = $subscription->package;
            $isActive  = !$subscription->end_date->endOfDay()->isPast();
            $isPro     = $this->isProPackage($package);
            $includesAi = $this->packageIncludesAi($package, $institutionId);
            $hasAi     = $this->packageGrantsAi($package, $institutionId);
            $unlimited = $this->packageAiUnlimited($package, $institutionId);

            return [
                'institution_id'      => $institutionId,
                'subscription'        => $subscription,
                'package'             => $package,
                'plan_name'           => $package->name,
                'is_active'           => $isActive,
                'is_pro'              => $isPro && $isActive,
                'includes_ai'         => $includesAi && $isActive,
                'has_ai'              => $hasAi && $isActive,
                'ai_platform_enabled' => $this->isAiPlatformEnabled(),
                'ai_unlimited'        => $unlimited,
                'days_left'           => max(0, (int) now()->diffInDays($subscription->end_date, false)),
            ];
        } catch (\Throwable $e) {
            return array_merge($empty, ['institution_id' => $institutionId]);
        }
    }

    public function isProPackage(?Package $package): bool
    {
        if (!$package) {
            return false;
        }

        if ($this->matchesProKeyword($package->name)) {
            return true;
        }

        if (Schema::hasColumn('packages', 'ai_unlimited') && $package->ai_unlimited) {
            return true;
        }

        if (Schema::hasColumn('packages', 'ai_enabled') && $package->ai_enabled) {
            return true;
        }

        $moduleCount = is_array($package->modules) ? count($package->modules) : 0;
        $threshold   = (int) config('plan.ai_module_threshold', 40);

        return $moduleCount >= $threshold;
    }

    public function isAiPlatformEnabled(): bool
    {
        $config = config('ai');
        if (! is_array($config)) {
            // Stale/missing config cache (e.g. before config/ai.php existed)
            return true;
        }

        return filter_var($config['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Whether the plan tier includes AI (ignores platform master switch).
     */
    public function packageIncludesAi(?Package $package, ?int $institutionId = null): bool
    {
        if ($institutionId && InstitutionSetting::get($institutionId, 'ai_enabled') === '1') {
            return true;
        }

        if (! $package) {
            return false;
        }

        if (Schema::hasColumn('packages', 'ai_enabled') && $package->ai_enabled) {
            return true;
        }

        if (Schema::hasColumn('packages', 'ai_unlimited') && $package->ai_unlimited) {
            return true;
        }

        if ($this->matchesProKeyword($package->name)) {
            return true;
        }

        $moduleCount = is_array($package->modules) ? count($package->modules) : 0;
        $threshold   = (int) config('plan.ai_module_threshold', 40);

        if ($moduleCount >= $threshold) {
            return true;
        }

        try {
            $totalModules = Module::count();
            if ($totalModules > 0 && $moduleCount >= ($totalModules - 2)) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return false;
    }

    public function packageGrantsAi(?Package $package, ?int $institutionId = null): bool
    {
        if (! $this->isAiPlatformEnabled()) {
            return false;
        }

        return $this->packageIncludesAi($package, $institutionId);
    }

    public function packageAiUnlimited(?Package $package, ?int $institutionId = null): bool
    {
        if ($institutionId && InstitutionSetting::get($institutionId, 'ai_unlimited') === '1') {
            return true;
        }

        if (!$package) {
            return false;
        }

        if (Schema::hasColumn('packages', 'ai_unlimited') && $package->ai_unlimited) {
            return true;
        }

        return $this->matchesProKeyword($package->name);
    }

    protected function matchesProKeyword(?string $name): bool
    {
        if (empty($name)) {
            return false;
        }
        $lower = strtolower($name);
        foreach (config('plan.pro_keywords', []) as $keyword) {
            if (str_contains($lower, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }
}
