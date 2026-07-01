<?php

namespace App\Http\Controllers;

use App\Models\AgentPayment;
use App\Models\AgentPaymentPeriod;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentPaymentController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('agent.page_title'));
    }

    public function index()
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $periods = AgentPaymentPeriod::latest()->get();
        $query = AgentPayment::with(['agent', 'period']);

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        }

        $payments = $query->latest()->paginate(20);
        $paidCount = (clone $query)->where('status', 'paid')->count();
        $unpaidCount = (clone $query)->where('status', 'unpaid')->count();
        $totalYtd = (clone $query)->where('status', 'paid')->whereYear('paid_at', now()->year)->sum('amount');

        $agents = $isSuperAdmin
            ? User::role(['Head Officer', 'School Admin'])->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('agent_payments.index', compact('periods', 'payments', 'paidCount', 'unpaidCount', 'totalYtd', 'agents', 'isSuperAdmin'));
    }

    public function storePeriod(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:120',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        AgentPaymentPeriod::create($request->only('label', 'start_date', 'end_date'));

        return back()->with('success', __('agent.period_created'));
    }

    public function storePayment(Request $request, NotificationService $notifications)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'agent_payment_period_id' => 'required|exists:agent_payment_periods,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $payment = AgentPayment::create([
            'user_id' => $request->user_id,
            'agent_payment_period_id' => $request->agent_payment_period_id,
            'amount' => $request->amount,
            'status' => 'unpaid',
            'notes' => $request->notes,
        ]);

        return back()->with('success', __('agent.payment_created'));
    }

    public function markPaid(Request $request, AgentPayment $payment, NotificationService $notifications)
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'processed_by' => Auth::id(),
        ]);

        $agent = $payment->agent;
        if ($agent?->phone) {
            $notifications->sendNotificationEvent('agent_payment_processed', $agent->phone, [
                'AgentName' => $agent->name,
                'Amount' => number_format((float) $payment->amount, 2),
                'Period' => $payment->period?->label ?? '',
                'SchoolName' => config('app.name'),
            ], $agent->institute_id, 'sms');
        }

        return back()->with('success', __('agent.payment_marked_paid'));
    }
}
