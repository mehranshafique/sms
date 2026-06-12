<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\InAppNotification;
use App\Models\Module;
use App\Models\Package;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * The "My Plan" page for a school (School Admin / Head Officer / Super Admin in a school context).
     */
    public function index()
    {
        $this->denyNonAdmins();
        $this->setPageTitle(__('plan.my_plan'));

        $institutionId = $this->getInstitutionId();

        // Super Admin in global mode has no single school context.
        if (!$institutionId) {
            if (Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
                return redirect()->route('plan.requests');
            }
            abort(403);
        }

        $subscription = $this->currentSubscription($institutionId);
        $package = $subscription?->package;

        $enabledCount = is_array($package?->modules) ? count($package->modules) : 0;
        $totalModules = Module::count();

        $availablePlans = Package::where('is_active', true)
            ->when($package, fn ($q) => $q->where('id', '!=', $package->id))
            ->orderBy('price')
            ->get();

        $pendingRequest = PlanUpgradeRequest::where('institution_id', $institutionId)
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->latest()
            ->first();

        $recentRequests = PlanUpgradeRequest::with('requestedPackage')
            ->where('institution_id', $institutionId)
            ->latest()
            ->limit(5)
            ->get();

        $planSnap = app(\App\Services\PlanContextService::class)->snapshot();

        return view('plan.index', [
            'subscription'    => $subscription,
            'package'         => $package,
            'enabledCount'    => $enabledCount,
            'totalModules'    => $totalModules,
            'availablePlans'  => $availablePlans,
            'pendingRequest'  => $pendingRequest,
            'recentRequests'  => $recentRequests,
            'daysLeft'        => $subscription ? max(0, (int) now()->diffInDays($subscription->end_date, false)) : null,
            'isPro'           => $planSnap['is_pro'] ?? false,
            'hasAi'           => $planSnap['includes_ai'] ?? false,
            'aiUsable'        => $planSnap['has_ai'] ?? false,
            'aiPlatformOn'    => $planSnap['ai_platform_enabled'] ?? true,
        ]);
    }

    public function requestUpgrade(Request $request)
    {
        $this->denyNonAdmins();

        $data = $request->validate([
            'requested_package_id' => 'nullable|exists:packages,id',
            'message'              => 'nullable|string|max:1000',
        ]);

        $institutionId = $this->getInstitutionId();
        abort_unless($institutionId, 403);

        // Avoid duplicate open requests
        $existing = PlanUpgradeRequest::where('institution_id', $institutionId)
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->exists();

        if ($existing) {
            return back()->with('warning', __('plan.request_already_pending'));
        }

        $current = $this->currentSubscription($institutionId);

        $upgrade = PlanUpgradeRequest::create([
            'institution_id'       => $institutionId,
            'user_id'              => Auth::id(),
            'current_package_id'   => $current?->package_id,
            'requested_package_id' => $data['requested_package_id'] ?? null,
            'message'              => $data['message'] ?? null,
            'status'               => PlanUpgradeRequest::STATUS_PENDING,
        ]);

        $this->notifySuperAdmins($upgrade);

        return back()->with('success', __('plan.request_sent'));
    }

    /**
     * Super Admin: list all upgrade requests.
     */
    public function requests(Request $request)
    {
        abort_unless(Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value), 403);
        $this->setPageTitle(__('plan.upgrade_requests'));

        $status = $request->get('status');

        $requests = PlanUpgradeRequest::with(['institution', 'user', 'currentPackage', 'requestedPackage'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all'      => PlanUpgradeRequest::count(),
            'pending'  => PlanUpgradeRequest::where('status', PlanUpgradeRequest::STATUS_PENDING)->count(),
            'contacted'=> PlanUpgradeRequest::where('status', PlanUpgradeRequest::STATUS_CONTACTED)->count(),
            'approved' => PlanUpgradeRequest::where('status', PlanUpgradeRequest::STATUS_APPROVED)->count(),
            'rejected' => PlanUpgradeRequest::where('status', PlanUpgradeRequest::STATUS_REJECTED)->count(),
        ];

        return view('plan.requests', compact('requests', 'counts', 'status'));
    }

    public function handleRequest(Request $request, PlanUpgradeRequest $planRequest)
    {
        abort_unless(Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value), 403);

        $data = $request->validate([
            'status' => 'required|in:pending,contacted,approved,rejected',
        ]);

        $appliedPackage = null;

        DB::transaction(function () use ($data, $planRequest, &$appliedPackage) {
            $planRequest->update([
                'status'     => $data['status'],
                'handled_by' => Auth::id(),
                'handled_at' => now(),
            ]);

            if ($data['status'] === PlanUpgradeRequest::STATUS_APPROVED) {
                $result = app(SubscriptionPlanService::class)->applyApprovedUpgrade($planRequest->fresh());
                $appliedPackage = $result['package'] ?? null;
            }
        });

        $planRequest->refresh();
        $this->notifyRequester($planRequest, $appliedPackage);

        if ($data['status'] === PlanUpgradeRequest::STATUS_APPROVED && $appliedPackage) {
            return back()->with('success', __('plan.request_approved_applied', ['plan' => $appliedPackage->name]));
        }

        if ($data['status'] === PlanUpgradeRequest::STATUS_APPROVED && !$appliedPackage) {
            return back()->with('warning', __('plan.request_approved_not_applied'));
        }

        return back()->with('success', __('plan.request_updated'));
    }

    /* ------------------------------------------------------------------ */

    protected function currentSubscription(int $institutionId): ?Subscription
    {
        return Subscription::with('package')
            ->where('institution_id', $institutionId)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending_payment' THEN 1 WHEN 'expired' THEN 2 ELSE 3 END")
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();
    }

    protected function denyNonAdmins(): void
    {
        $user = Auth::user();
        $allowed = $user && $user->hasAnyRole([
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
        ]);
        abort_unless($allowed, 403);
    }

    protected function notifySuperAdmins(PlanUpgradeRequest $upgrade): void
    {
        try {
            $institutionName = $upgrade->institution?->name ?? '';
            $requested = $upgrade->requestedPackage?->name ?? __('plan.any_higher_plan');

            $admins = User::role(RoleEnum::SUPER_ADMIN->value)->get();
            foreach ($admins as $admin) {
                InAppNotification::create([
                    'user_id'        => $admin->id,
                    'institution_id' => $upgrade->institution_id,
                    'type'           => 'plan_upgrade',
                    'title'          => __('plan.notif_new_request_title'),
                    'message'        => __('plan.notif_new_request_message', [
                        'school' => $institutionName,
                        'plan'   => $requested,
                    ]),
                    'link'           => route('plan.requests'),
                    'icon'           => 'fa-arrow-up',
                    'meta'           => ['plan_upgrade_request_id' => $upgrade->id],
                ]);
            }
        } catch (\Throwable $e) {
            // Notifications must never block the request.
        }
    }

    protected function notifyRequester(PlanUpgradeRequest $upgrade, ?Package $appliedPackage = null): void
    {
        try {
            $message = $upgrade->status === PlanUpgradeRequest::STATUS_APPROVED && $appliedPackage
                ? __('plan.notif_approved_message', ['plan' => $appliedPackage->name])
                : __('plan.notif_status_message', [
                    'status' => __('plan.status_' . $upgrade->status),
                ]);

            InAppNotification::create([
                'user_id'        => $upgrade->user_id,
                'institution_id' => $upgrade->institution_id,
                'type'           => 'plan_upgrade',
                'title'          => $upgrade->status === PlanUpgradeRequest::STATUS_APPROVED
                    ? __('plan.notif_approved_title')
                    : __('plan.notif_status_title'),
                'message'        => $message,
                'link'           => route('plan.index'),
                'icon'           => 'fa-info-circle',
                'meta'           => ['plan_upgrade_request_id' => $upgrade->id],
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
