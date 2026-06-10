@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Welcome Banner --}}
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card bg-primary text-white shadow-sm border-0">
                    <div class="card-body d-flex justify-content-between align-items-center p-4">
                        <div>
                            <h3 class="text-white fw-bold mb-1">
                                {{ __('dashboard.welcome_back') }}, {{ Auth::user()->name }}
                            </h3>
                            <p class="mb-0 opacity-75">
                                {{ __('dashboard.accountant_dashboard') }} |
                                @if(isset($currentSession))
                                    {{ __('dashboard.current_session') }}: <strong>{{ $currentSession->name }}</strong>
                                @else
                                    {{ __('dashboard.no_active_session') }}
                                @endif
                            </p>
                        </div>
                        <i class="la la-calculator opacity-25" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- QUICK ACTIONS --}}
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="text-primary mb-3 fw-bold"><i class="la la-bolt"></i> {{ __('dashboard.quick_actions') }}</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary shadow-sm mb-2 me-2"><i class="la la-file-invoice me-1"></i> {{ __('dashboard.create_invoice') }}</a>
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-success shadow-sm mb-2 me-2"><i class="la la-money-bill-wave me-1"></i> {{ __('dashboard.record_payment') }}</a>
                    <a href="{{ route('finance.balances.index') }}" class="btn btn-outline-info shadow-sm mb-2 me-2"><i class="la la-balance-scale me-1"></i> {{ __('dashboard.student_balances') }}</a>
                    <a href="{{ route('finance.reports.class_summary') }}" class="btn btn-outline-warning shadow-sm mb-2 me-2"><i class="la la-chart-pie me-1"></i> {{ __('dashboard.class_reports') }}</a>
                    <a href="{{ route('budgets.requests') }}" class="btn btn-outline-danger shadow-sm mb-2 me-2"><i class="la la-hand-holding-usd me-1"></i> {{ __('dashboard.fund_requests') }}</a>
                </div>
            </div>
        </div>

        {{-- ROW 1: FINANCIAL FORECASTS & ACTUALS --}}
        <div class="row">
            {{-- Expected Revenue (Forecast) --}}
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info">
                                <i class="la la-chart-bar"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1 text-muted">{{ __('dashboard.financial_forecast') }}</p>
                                <h3 class="mb-0 fw-bold text-dark">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($expectedTotal, 2) }}</h3>
                                <small class="text-info">{{ __('dashboard.based_on_active_students', ['count' => $activeStudentsCount]) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Collected Revenue (Actuals) --}}
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success">
                                <i class="la la-wallet"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1 text-muted">{{ __('dashboard.collected_revenue') }}</p>
                                <h3 class="mb-0 fw-bold text-success">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($collectedTotal, 2) }}</h3>
                                
                                @php
                                    $collectionPercent = $expectedTotal > 0 ? ($collectedTotal / $expectedTotal) * 100 : 0;
                                @endphp
                                <div class="progress mt-2 mb-1" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: {{ $collectionPercent }}%"></div>
                                </div>
                                <small class="text-muted">{{ number_format($collectionPercent, 1) }}{{ __('dashboard.percent_collected') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Remaining Balance (Outstanding) --}}
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-danger text-danger">
                                <i class="la la-file-invoice-dollar"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1 text-muted">{{ __('dashboard.remaining_balance') }}</p>
                                <h3 class="mb-0 fw-bold text-danger">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($remainingToCollect, 2) }}</h3>
                                <small class="text-danger">{{ __('dashboard.to_be_collected') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 2: INSTALLMENTS (TRANCHES) & BUDGET OVERVIEW --}}
        <div class="row">
            {{-- Installments / Tranches --}}
            <div class="col-xl-6 col-lg-12">
                <div class="card shadow-sm border-0" style="min-height: 300px;">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.installments_tranches') }}</h4>
                    </div>
                    <div class="card-body">
                        @if(count($installmentStats) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>{{ __('dashboard.tranche_installment') }}</th>
                                            <th class="text-end">{{ __('dashboard.expected_revenue') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($installmentStats as $stat)
                                        <tr>
                                            <td class="py-3">
                                                <span class="badge badge-xs light badge-primary me-2">{{ $stat['order'] }}</span>
                                                <span class="fw-bold text-dark">{{ $stat['label'] }}</span>
                                            </td>
                                            <td class="text-end py-3 fw-bold text-primary">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($stat['expected'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="la la-coins fs-24 mb-2"></i><br>
                                {{ __('dashboard.no_installments_found') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Budget Overview --}}
            <div class="col-xl-6 col-lg-12">
                <div class="card shadow-sm border-0" style="min-height: 300px;">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.budget_overview') }}</h4>
                        <a href="{{ route('budgets.index') }}" class="btn btn-primary btn-xs">{{ __('dashboard.manage_budget') }}</a>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="row text-center mb-4 mt-2">
                            <div class="col-6 border-end">
                                <span class="text-muted d-block mb-1">{{ __('dashboard.total_allocated') }}</span>
                                <h3 class="fw-bold text-dark mb-0">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalBudget, 2) }}</h3>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block mb-1">{{ __('dashboard.total_spent') }}</span>
                                <h3 class="fw-bold text-danger mb-0">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($budgetSpent, 2) }}</h3>
                            </div>
                        </div>

                        @php
                            $budgetPercent = $totalBudget > 0 ? ($budgetSpent / $totalBudget) * 100 : 0;
                            $budgetRest = $totalBudget - $budgetSpent;
                        @endphp
                        
                        <div class="px-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold">{{ __('dashboard.budget_utilization') }}</span>
                                <span class="fw-bold">{{ number_format($budgetPercent, 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-{{ $budgetPercent > 85 ? 'danger' : 'primary' }}" 
                                     style="width: {{ $budgetPercent }}%"></div>
                            </div>
                            <p class="mt-3 text-center text-muted">
                                {{ __('dashboard.remaining_budget') }}: <strong>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($budgetRest, 2) }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 3: RECENT TRANSACTIONS --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('dashboard.recent_transactions') }}</h4>
                        <a href="{{ route('invoices.index') }}" class="btn-link">{{ __('dashboard.view_all') }}</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-responsive-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('dashboard.date') }}</th>
                                        <th>{{ __('dashboard.student') }}</th>
                                        <th>{{ __('dashboard.payment_ref') }}</th>
                                        <th>{{ __('dashboard.method') }}</th>
                                        <th class="text-end">{{ __('dashboard.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentPayments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong class="text-dark">{{ $payment->invoice->student->full_name ?? __('dashboard.unknown_student') }}</strong>
                                                <small class="text-muted">{{ $payment->invoice->student->admission_number ?? '' }}</small>
                                            </div>
                                        </td>
                                        <td><span class="badge light badge-light">{{ $payment->reference_number ?? __('dashboard.not_available') }}</span></td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $payment->method ?? $payment->payment_method)) }}</td>
                                        <td class="text-end fw-bold text-success">+{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">{{ __('dashboard.no_recent_payments') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection