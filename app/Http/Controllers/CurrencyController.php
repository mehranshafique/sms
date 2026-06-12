<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrencyController extends BaseController
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {
        $this->middleware('auth');
        $this->setPageTitle(__('currency.page_title'));
    }

    public function index()
    {
        $this->authorizeAdminOrPermission('currency.view');

        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if (!$institutionId && !$user->hasRole('Super Admin')) {
            return redirect()->route('dashboard')->with('error', __('currency.select_institution'));
        }

        $isGlobal = $user->hasRole('Super Admin') && is_null($institutionId);
        $settings = $this->currencyService->getSettings($institutionId);
        $currencies = $this->currencyService->supported();

        return view('currency.index', compact('settings', 'currencies', 'institutionId', 'isGlobal'));
    }

    public function update(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if (!$user->can('currency.update') && !$this->userIsSchoolAdmin($user)) {
            abort(403);
        }

        if (!$institutionId && !$user->hasRole('Super Admin')) {
            abort(403, __('currency.select_institution'));
        }

        $request->validate([
            'currency_code' => 'required|string|max:10',
            'currency_position' => 'required|in:before,after',
            'currency_symbol' => 'nullable|string|max:12',
            'currency_decimals' => 'nullable|integer|min:0|max:4',
        ]);

        $this->currencyService->save(
            $institutionId,
            $request->currency_code,
            $request->currency_position,
            $request->currency_symbol,
            (int) ($request->currency_decimals ?? 2)
        );

        return response()->json(['message' => __('currency.saved')]);
    }
}
