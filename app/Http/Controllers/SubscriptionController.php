<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institution;
use App\Models\Subscription;
use App\Models\Package;
use App\Models\PlatformInvoice;
use App\Models\Module;
use App\Enums\RoleEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\AuditLogger;
use App\Services\SubscriptionPlanService;
use Barryvdh\DomPDF\Facade\Pdf;

class SubscriptionController extends BaseController
{
    public function __construct()
    {
        // $this->middleware(['auth', RoleEnum::SUPER_ADMIN->value]);
        $this->setPageTitle(__('subscription.page_title'));
    }

    // --- PACKAGES ---
    public function indexPackages()
    {
        $packages = Package::latest()->get();
        $modules = Module::orderBy('name')->get();
        return view('finance.subscription.packages', compact('packages', 'modules'));
    }

    public function storePackage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'modules' => 'nullable|array',
            'student_limit' => 'nullable|integer',
            'ai_monthly_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        Package::create([
            'name' => $request->name,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'modules' => $request->modules ?? [],
            'student_limit' => $request->student_limit,
            'staff_limit' => $request->staff_limit, 
            'ai_enabled' => $request->boolean('ai_enabled'),
            'ai_unlimited' => $request->boolean('ai_unlimited'),
            'ai_monthly_limit' => $request->filled('ai_monthly_limit') ? (int) $request->ai_monthly_limit : null,
            'is_active' => true 
        ]);

        AuditLogger::log('Create', 'Package', 'Created package: ' . $request->name);
        return $this->successResponse(__('subscription.success_create'), route('packages.index'));
    }

    public function editPackage(Package $package)
    {
        $modules = Module::orderBy('name')->get();
        return view('finance.subscription.edit_package', compact('package', 'modules'));
    }

    public function updatePackage(Request $request, Package $package)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'modules' => 'nullable|array',
            'student_limit' => 'nullable|integer',
            'ai_monthly_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        $package->update([
            'name' => $request->name,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'modules' => $request->modules ?? [],
            'student_limit' => $request->student_limit,
            'staff_limit' => $request->staff_limit, 
            'ai_enabled' => $request->boolean('ai_enabled'),
            'ai_unlimited' => $request->boolean('ai_unlimited'),
            'ai_monthly_limit' => $request->filled('ai_monthly_limit') ? (int) $request->ai_monthly_limit : null,
            'is_active' => $request->has('is_active')
        ]);

        $package->refresh();
        $this->ensureProPackageAiFlags($package);

        // Propagate the updated module set to every school currently on this plan
        $this->syncPackageModulesToInstitutions($package);
        $this->syncPackageAiToInstitutions($package);

        AuditLogger::log('Update', 'Package', 'Updated package: ' . $package->name);
        return $this->successResponse(__('subscription.success_update'), route('packages.index'));
    }

    /**
     * Re-sync a package's module set onto every institution that currently has
     * an active subscription to it, so edits to a plan take effect live.
     */
    private function syncPackageModulesToInstitutions(Package $package): void
    {
        $modules = json_encode($package->modules ?? []);

        Subscription::where('package_id', $package->id)
            ->where('status', 'active')
            ->pluck('institution_id')
            ->unique()
            ->each(function ($institutionId) use ($modules) {
                \App\Models\InstitutionSetting::set($institutionId, 'enabled_modules', $modules, 'modules');
            });
    }

    /**
     * Sync AI flags onto every institution subscribed to this package.
     */
    private function syncPackageAiToInstitutions(Package $package): void
    {
        $planService = app(SubscriptionPlanService::class);

        Subscription::where('package_id', $package->id)
            ->where('status', 'active')
            ->pluck('institution_id')
            ->unique()
            ->each(fn ($institutionId) => $planService->syncInstitutionFromPackage($institutionId, $package));
    }

    /**
     * Pro / full-tier packages always get AI flags when saved.
     */
    private function ensureProPackageAiFlags(Package $package): void
    {
        $planContext = app(\App\Services\PlanContextService::class);
        if (! $planContext->isProPackage($package)) {
            return;
        }

        $updates = [];
        if (! $package->ai_enabled) {
            $updates['ai_enabled'] = true;
        }
        if (! $package->ai_unlimited && $planContext->isProPackage($package)) {
            $updates['ai_unlimited'] = true;
        }
        if (! empty($updates)) {
            $package->update($updates);
        }
    }

    public function destroyPackage(Package $package)
    {
        if($package->subscriptions()->exists()) {
             $package->update(['is_active' => false]);
             return back()->with('warning', __('subscription.package_in_use'));
        }
        $package->delete();
        AuditLogger::log('Delete', 'Package', 'Deleted package: ' . $package->name);
        return back()->with('success', __('subscription.success_delete'));
    }

    // --- SUBSCRIPTIONS ---
    public function index(Request $request)
    {
        $subscriptions = Subscription::with(['institution', 'package'])
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('subscriptions')
                    ->groupBy('institution_id');
            })
            ->latest()
            ->get();

        $stats = [
            'total'    => $subscriptions->count(),
            'active'   => $subscriptions->filter(fn ($s) => $s->displayStatus() === 'active')->count(),
            'expiring' => $subscriptions->filter(fn ($s) => !$s->isExpired() && $s->daysLeft() <= 30)->count(),
            'expired'  => $subscriptions->filter(fn ($s) => $s->isExpired())->count(),
        ];

        return view('finance.subscription.index', compact('subscriptions', 'stats'));
    }

    public function create()
    {
        $institutions = Institution::pluck('name', 'id');
        $packages = Package::where('is_active', true)->pluck('name', 'id');
        return view('finance.subscription.create', compact('institutions', 'packages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'package_id' => 'required|exists:packages,id',
            'start_date' => 'required|date',
            'payment_method' => 'required|string',
            'status' => 'required|in:active,pending_payment',
            'generate_invoice' => 'boolean'
        ]);

        DB::transaction(function () use ($request) {
            $package = Package::findOrFail($request->package_id);
            $startDate = Carbon::parse($request->start_date);
            // Fix: Cast duration_days to int to prevent Carbon error
            $endDate = $startDate->copy()->addDays((int) $package->duration_days);

            $sub = Subscription::create([
                'institution_id' => $request->institution_id,
                'package_id' => $package->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $request->status,
                'price_paid' => $package->price,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            if ($request->generate_invoice) {
                PlatformInvoice::create([
                    'invoice_number' => 'INV-PLT-' . strtoupper(uniqid()),
                    'institution_id' => $request->institution_id,
                    'subscription_id' => $sub->id,
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(7),
                    'total_amount' => $package->price,
                    'status' => ($request->status === 'active' && $request->payment_method == 'Manual') ? 'paid' : 'unpaid',
                ]);
            }

            // Sync Modules
            \App\Models\InstitutionSetting::set(
                $request->institution_id, 
                'enabled_modules', 
                json_encode($package->modules), 
                'modules'
            );
        });

        return $this->successResponse(__('subscription.subscription_assigned'), route('subscriptions.index'));
    }

    public function edit(Subscription $subscription)
    {
        $institutions = Institution::pluck('name', 'id');
        $packages = Package::where('is_active', true)->pluck('name', 'id');
        return view('finance.subscription.edit', compact('subscription', 'institutions', 'packages'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,expired,cancelled,pending_payment',
            'payment_method' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $subscription) {
            $subscription->update([
                'package_id' => $request->package_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            $package = Package::findOrFail($request->package_id);
            app(SubscriptionPlanService::class)->syncInstitutionFromPackage($subscription->institution_id, $package);
        });

        AuditLogger::log('Update', 'Subscription', 'Updated subscription #' . $subscription->id);
        return $this->successResponse(__('subscription.subscription_updated'), route('subscriptions.index'));
    }

    public function destroy(Subscription $subscription)
    {
        DB::transaction(function () use ($subscription) {
            if ($subscription->invoices()->exists()) {
                $subscription->update(['status' => 'cancelled']);
            } else {
                $subscription->delete();
            }
        });

        AuditLogger::log('Delete', 'Subscription', 'Removed subscription #' . $subscription->id);
        return redirect()->route('subscriptions.index')->with('success', __('subscription.success_delete'));
    }

    // --- INVOICES ---
    public function invoices(Request $request)
    {
        $user = Auth::user();
        $query = PlatformInvoice::with(['institution', 'subscription.package']);

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            // Super Admin sees all
        } elseif ($user->hasRole(RoleEnum::HEAD_OFFICER->value)) {
            // Head Officer sees their institution's invoices
            $institutionId = $this->getInstitutionId();
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            } else {
                $allowedIds = $user->institutes->pluck('id');
                $query->whereIn('institution_id', $allowedIds);
            }
        } else {
            abort(403, 'Unauthorized');
        }

        $invoices = $query->latest()->get();
        return view('finance.subscription.invoices', compact('invoices'));
    }

    public function showInvoice($id)
    {
        $invoice = PlatformInvoice::with(['institution', 'subscription.package'])->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);
        
        return view('finance.subscription.show_invoice', compact('invoice'));
    }

    public function printInvoice($id)
    {
        $invoice = PlatformInvoice::with(['institution', 'subscription.package'])->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);
        $isPdf = false;
        
        return view('finance.subscription.print_invoice', compact('invoice', 'isPdf'));
    }

    public function downloadInvoicePdf($id)
    {
        $invoice = PlatformInvoice::with(['institution', 'subscription.package'])->findOrFail($id);
        $this->authorizeInvoiceAccess($invoice);
        $isPdf = true;
        
        if (class_exists('PDF')) {
            $pdf = Pdf::loadView('finance.subscription.print_invoice', compact('invoice', 'isPdf'));
            return $pdf->download('Invoice-'.$invoice->invoice_number.'.pdf');
        } else {
            return redirect()->route('subscriptions.invoices.print', $id);
        }
    }

    /**
     * Authorize user access to a specific invoice.
     */
    private function authorizeInvoiceAccess($invoice)
    {
        $user = Auth::user();
        
        // 1. Super Admin: Allow
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }
        
        // 2. Head Officer: Check Ownership
        if ($user->hasRole(RoleEnum::HEAD_OFFICER->value)) {
            $institutionId = $this->getInstitutionId();
            
            // Context Check
            if ($institutionId && $invoice->institution_id == $institutionId) {
                return true;
            }
            
            // Pivot Check (if they haven't switched context but own the school)
            if ($user->institutes->contains('id', $invoice->institution_id)) {
                return true;
            }
        }
        
        abort(403, 'Unauthorized');
    }
}