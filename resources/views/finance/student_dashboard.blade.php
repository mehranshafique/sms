@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Page Header --}}
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('finance.student_finance_dashboard') }}</h4>
                    <p class="mb-0">{{ $student->full_name }} ({{ $student->admission_number }})</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('students.show', $student->id) }}" class="btn btn-secondary">{{ __('finance.back_to_profile') }}</a>
            </div>
        </div>

        <div class="row">
            {{-- Tab Navigation --}}
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('finance.fee_management') }}</h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            {{-- Global Overview Tab --}}
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#global-overview">
                                    <i class="la la-globe me-2"></i> {{ __('finance.global_overview') }}
                                </a>
                            </li>

                            {{-- Dynamic Installment Tabs --}}
                            @foreach($tabs as $index => $tab)
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#installment-{{ $index }}">
                                        <i class="la la-money me-2"></i> {{ $tab['label'] }}
                                        @if($tab['remaining'] > 0)
                                            <span class="badge badge-danger badge-xs ms-1">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['remaining']) }}</span>
                                        @else
                                            <span class="badge badge-success badge-xs ms-1"><i class="fa fa-check"></i></span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content mt-4">
                            
                            {{-- 1. Global Overview Content --}}
                            <div class="tab-pane fade show active" id="global-overview" role="tabpanel">
                                @php
                                    $hasDiscount = isset($discountAmount) && $discountAmount > 0;
                                    $colClass = $hasDiscount ? 'col-md-3' : 'col-md-4';
                                @endphp

                                <div class="row text-center">
                                    {{-- Annual Fee Card --}}
                                    <div class="{{ $colClass }}">
                                        <div class="widget-stat card bg-primary text-white">
                                            <div class="card-body">
                                                <div class="media">
                                                    <div class="media-body">
                                                        <p class="mb-1">
                                                            {{ $hasDiscount ? __('finance.annual_fee_gross') : __('finance.annual_fee_contract') }}
                                                        </p>
                                                        <h3 class="text-white">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($hasDiscount ? $grossAnnualFee : $displayAnnualFee, 2) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Discount Card (Visible Only if Discount Exists) --}}
                                    @if($hasDiscount)
                                    <div class="{{ $colClass }}">
                                        <div class="widget-stat card bg-info text-white">
                                            <div class="card-body">
                                                <div class="media">
                                                    <div class="media-body text-white">
                                                        <p class="mb-1">{{ __('finance.discount_applied') }}</p>
                                                        <h3 class="text-white">- {{ \App\Enums\CurrencySymbol::default() }} {{ number_format($discountAmount, 2) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Total Paid Card --}}
                                    <div class="{{ $colClass }}">
                                        <div class="widget-stat card bg-success text-white">
                                            <div class="card-body">
                                                <div class="media">
                                                    <div class="media-body">
                                                        <p class="mb-1">{{ __('finance.total_paid_global') }}</p>
                                                        <h3 class="text-white">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalPaidGlobal, 2) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Remaining Card --}}
                                    <div class="{{ $colClass }}">
                                        <div class="widget-stat card bg-warning text-white">
                                            <div class="card-body">
                                                <div class="media">
                                                    <div class="media-body">
                                                        <p class="mb-1">{{ __('finance.total_remaining_year') }}</p>
                                                        <h3 class="text-white">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalDueGlobal, 2) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Summary List --}}
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>{{ __('finance.installment_label') }}</th>
                                                <th>{{ __('finance.amount') }}</th>
                                                <th>{{ __('finance.paid') }}</th>
                                                <th>{{ __('finance.remaining') }}</th>
                                                <th>{{ __('finance.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tabs as $tab)
                                                @php
                                                    $badgeClass = 'danger'; // Default Unpaid
                                                    if($tab['status'] == 'Paid') $badgeClass = 'success';
                                                    elseif($tab['status'] == 'Partial') $badgeClass = 'warning'; // Orange/Yellow
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $tab['label'] }}</strong></td>
                                                    <td>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['amount'], 2) }}</td>
                                                    <td>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['paid'], 2) }}</td>
                                                    <td class="text-danger fw-bold">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['remaining'], 2) }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $badgeClass }}">
                                                            {{ $tab['status'] }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- 2. Installment Tabs Content --}}
                            @foreach($tabs as $index => $tab)
                                <div class="tab-pane fade" id="installment-{{ $index }}" role="tabpanel">
                                    
                                    @if($tab['is_locked'])
                                        {{-- Locked State --}}
                                        <div class="alert alert-danger solid alert-dismissible fade show">
                                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="me-2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                            <strong>{{ __('finance.locked_msg', ['label' => $tab['label']]) }}</strong>
                                        </div>
                                    @else
                                        {{-- Active State --}}
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h4 class="text-primary mb-3">{{ __('finance.payment_for', ['label' => $tab['label']]) }}</h4>
                                                
                                                <ul class="list-group list-group-flush mb-4">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        {{ __('finance.amount') }}
                                                        <span class="badge badge-primary badge-pill">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['amount'], 2) }}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        {{ __('finance.already_paid') }}
                                                        <span class="badge badge-success badge-pill">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['paid'], 2) }}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <strong>{{ __('finance.remaining_due') }}</strong>
                                                        <span class="badge badge-danger badge-pill fs-16">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['remaining'], 2) }}</span>
                                                    </li>
                                                </ul>

                                                @if($tab['remaining'] > 0)
                                                    <a href="{{ route('payments.create', ['invoice_id' => $tab['id']]) }}" class="btn btn-success btn-lg btn-block">
                                                        <i class="la la-credit-card me-2"></i> {{ __('finance.pay_now') }} ({{ $tab['label'] }})
                                                    </a>
                                                @else
                                                    <div class="alert alert-success solid">
                                                        <i class="fa fa-check-circle me-2"></i> {{ __('finance.fully_settled') }}
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Context Info (Intelligent View) --}}
                                            <div class="col-md-6 border-start">
                                                <h5 class="text-muted mb-3">{{ __('finance.context_history') }}</h5>
                                                <div class="p-3 bg-light rounded">
                                                    @if(isset($discountAmount) && $discountAmount > 0)
                                                        {{-- Gross Fee --}}
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <small class="text-muted">{{ __('finance.annual_fee_gross') }}:</small>
                                                            <strong>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($grossAnnualFee, 2) }}</strong>
                                                        </div>
                                                        
                                                        {{-- Discount Applied --}}
                                                        <div class="d-flex justify-content-between align-items-center mb-1 text-success">
                                                            <small>{{ __('finance.discount_applied') }}:</small>
                                                            <strong>- {{ \App\Enums\CurrencySymbol::default() }} {{ number_format($discountAmount, 2) }}</strong>
                                                        </div>
                                                        <hr class="my-1">
                                                    @endif

                                                    {{-- Net Annual Fee --}}
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small>{{ __('finance.annual_fee_contract') }}:</small>
                                                        <strong>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($netAnnualFee ?? $displayAnnualFee ?? 0, 2) }}</strong>
                                                    </div>

                                                    {{-- Current Installment --}}
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small>{{ __('finance.current_installment') }} ({{ $tab['label'] }}):</small>
                                                        <strong>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($tab['amount'], 2) }}</strong>
                                                    </div>
                                                    
                                                    <hr>
                                                    
                                                    {{-- Reduce Global Msg --}}
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-dark small">{{ __('finance.reduce_global_msg') }}</span>
                                                        <strong class="text-dark">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format(max(0, $totalDueGlobal - $tab['remaining']), 2) }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Payment History for this Installment --}}
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card border">
                                                    <div class="card-header bg-light py-2 px-3 border-bottom">
                                                        <h6 class="mb-0 text-primary">
                                                            <i class="la la-history me-1"></i> {{ __('finance.payment_history') ?? 'Payment History' }}: {{ $tab['label'] }}
                                                        </h6>
                                                    </div>
                                                    <div class="card-body p-0">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-hover mb-0">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th>{{ __('finance.date') ?? 'Date' }}</th>
                                                                        <th>{{ __('finance.transaction_id') ?? 'Transaction ID' }}</th>
                                                                        <th>{{ __('finance.method') ?? 'Method' }}</th>
                                                                        <th class="text-end">{{ __('finance.amount') }}</th>
                                                                        <th>{{ __('finance.recorded_by') ?? 'Recorded By' }}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @forelse($tab['invoice']->payments as $payment)
                                                                        <tr>
                                                                            <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                                                                            <td><span class="badge badge-xs badge-light text-dark border">{{ $payment->transaction_id }}</span></td>
                                                                            <td>{{ ucfirst($payment->method) }}</td>
                                                                            <td class="text-end text-success fw-bold">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($payment->amount, 2) }}</td>
                                                                            <td class="text-muted small">{{ $payment->receivedBy->name ?? 'System' }}</td>
                                                                        </tr>
                                                                    @empty
                                                                        <tr>
                                                                            <td colspan="5" class="text-center text-muted py-3">{{ __('finance.no_payments_found') ?? 'No payments recorded for this installment yet.' }}</td>
                                                                        </tr>
                                                                    @endforelse
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection