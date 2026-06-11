<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Services\PaymentMethodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PaymentMethodController extends BaseController
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService,
        protected \App\Services\PaymentGateways\PaymentGatewayConfigService $gatewayConfigService
    ) {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':invoice.view')->only(['index']);
        $this->setPageTitle(__('payment_methods.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if (!$institutionId) {
            return redirect()->route('dashboard')->with('error', __('payment_methods.select_institution'));
        }

        if (!$user->can('invoice.view') && !$user->hasRole(['Super Admin', 'School Admin', 'Head Officer', 'Accountant', 'accountant'])) {
            abort(403);
        }

        $config = $this->paymentMethodService->getConfig($institutionId);
        $definitions = PaymentMethodService::DEFINITIONS;
        $gatewayConfig = $this->gatewayConfigService->getConfig($institutionId);
        $gatewayProviders = config('payment_gateways.providers', []);

        return view('finance.payment_methods.index', compact('config', 'definitions', 'institutionId', 'gatewayConfig', 'gatewayProviders'));
    }

    public function update(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if (!$institutionId) {
            abort(403, __('payment_methods.select_institution'));
        }

        if (!$user->can('payment.create') && !$user->hasRole(['Super Admin', 'School Admin', 'Head Officer', 'Accountant', 'accountant'])) {
            abort(403);
        }

        $request->validate([
            'online_payments_enabled' => 'sometimes|boolean',
            'methods' => 'required|array',
            'provider' => 'nullable|string|in:none,pawapay,cinetpay,flutterwave',
            'environment' => 'nullable|string|in:sandbox,production',
            'manual_proof_enabled' => 'sometimes|boolean',
            'credentials' => 'nullable|array',
        ]);

        $input = $request->all();
        $input['online_payments_enabled'] = $request->boolean('online_payments_enabled');
        $input['manual_proof_enabled'] = $request->boolean('manual_proof_enabled');

        $this->paymentMethodService->saveConfig($institutionId, $input);
        $this->gatewayConfigService->saveConfig($institutionId, $input);

        return response()->json(['message' => __('payment_methods.saved')]);
    }
}
