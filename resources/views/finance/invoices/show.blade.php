@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('invoice.invoice_details') }}</h4>
                    <p class="mb-0 text-muted">#{{ $invoice->invoice_number }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-dark btn-sm"><i class="fa fa-arrow-left me-1"></i> {{ __('invoice.back') }}</a>
                <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="btn btn-info btn-sm shadow-sm"><i class="fa fa-print me-1"></i> {{ __('invoice.print_web_view') }}</a>
                <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-success btn-sm shadow-sm"><i class="fa fa-file-pdf me-1"></i> {{ __('invoice.download_pdf') }}</a>
            </div>
        </div>

        @php
            $currency = \App\Enums\CurrencySymbol::default();
            $dueAmount = $invoice->total_amount - $invoice->paid_amount;
            
            $statusBadges = [
                'paid' => 'badge-success',
                'partial' => 'badge-warning',
                'unpaid' => 'badge-danger',
                'overdue' => 'badge-dark',
            ];
            $badgeClass = $statusBadges[$invoice->status] ?? 'badge-secondary';

            // Installment Calculation Logic
            $installmentSuffix = '';
            $firstItem = $invoice->items->first();
            if ($firstItem && $firstItem->feeStructure && $firstItem->feeStructure->payment_mode === 'installment') {
                $currentOrder = $firstItem->feeStructure->installment_order ?: 1;
                
                $totalInstallments = \App\Models\FeeStructure::where('institution_id', $invoice->institution_id)
                    ->where('academic_session_id', $invoice->academic_session_id)
                    ->where('fee_type_id', $firstItem->feeStructure->fee_type_id)
                    ->where('payment_mode', 'installment')
                    ->where(function($q) use ($firstItem) {
                        if ($firstItem->feeStructure->class_section_id) {
                            $q->where('class_section_id', $firstItem->feeStructure->class_section_id);
                        } else {
                            $q->where('grade_level_id', $firstItem->feeStructure->grade_level_id);
                        }
                    })
                    ->count();
                    
                $total = $totalInstallments > 0 ? $totalInstallments : 1;
                $installmentSuffix = " ({$currentOrder}/{$total})";
            }
        @endphp

        <div class="row">
            @if(!empty($planCtx['has_ai']) && in_array($invoice->status, ['unpaid', 'partial', 'overdue']))
            <div class="col-lg-12 mb-3">
                <div class="ai-copilot-card">
                    <div class="ai-copilot-card__head">
                        <div>
                            <strong><i class="la la-magic me-1"></i> {{ __('ai.tools.invoice_insights') }}</strong>
                            <div class="text-muted small">{{ __('ai.tools.invoice_insights_desc') }}</div>
                        </div>
                        @include('ai.partials.embed-button', [
                            'tool' => 'invoice_insights',
                            'params' => ['invoice_id' => $invoice->id],
                            'label' => __('ai.btn_invoice_insights'),
                            'panel' => '#ai-invoice-insights',
                        ])
                    </div>
                    <div class="ai-embed-panel" id="ai-invoice-insights"></div>
                </div>
            </div>
            @endif
            <div class="col-lg-12">
                <div class="card mt-3 border-0 shadow-sm">
                    <!-- Modern Header -->
                    <div class="card-header bg-primary text-white py-4 d-flex justify-content-between align-items-center rounded-top"> 
                        <div>
                            <h3 class="text-white mb-0 fw-bold">{{ mb_strtoupper($invoice->status === 'paid' ? __('invoice.receipt') : __('invoice.invoice')) }}</h3>
                            <span class="opacity-75">{{ __('invoice.date') }}: {{ $invoice->issue_date->format('d M, Y') }}</span>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $badgeClass }} fs-14 px-3 py-2 text-uppercase shadow-sm border border-light">
                                {{ mb_strtoupper(__('invoice.status_' . $invoice->status) ?? $invoice->status) }}{{ $installmentSuffix }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row mb-5">
                            <div class="col-sm-6">
                                <h6 class="text-primary fw-bold text-uppercase mb-3">{{ __('invoice.from_institution') }}</h6>
                                <div><strong>{{ $invoice->institution->name ?? config('app.name') }}</strong></div>
                                <div class="text-muted">{{ $invoice->institution->address ?? 'N/A' }}</div>
                                <div class="text-muted">{{ $invoice->institution->email ?? '' }}</div>
                                <div class="text-muted">{{ $invoice->institution->phone ?? '' }}</div>
                            </div>
                            <div class="col-sm-6 text-sm-end mt-4 mt-sm-0">
                                <h6 class="text-primary fw-bold text-uppercase mb-3">{{ __('invoice.billed_to') }}</h6>
                                <div class="bg-light p-3 rounded d-inline-block text-start" style="min-width: 250px;">
                                    <h5 class="fw-bold mb-1">{{ $invoice->student->full_name }}</h5>
                                    <div><span class="text-muted">{{ __('invoice.admission_no') }}:</span> <strong>{{ $invoice->student->admission_number }}</strong></div>
                                    @php
                                        $enrollment = $invoice->student->enrollments()->where('academic_session_id', $invoice->academic_session_id)->first() ?? $invoice->student->enrollments()->latest()->first();
                                    @endphp
                                    <div><span class="text-muted">{{ __('invoice.class') }}:</span> <strong>{{ $enrollment->classSection->name ?? 'N/A' }}</strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Items -->
                        <div class="table-responsive mb-5">
                            <table class="table table-striped border">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="py-3">#</th>
                                        <th class="py-3">{{ __('invoice.description') }}</th>
                                        <th class="py-3 text-end">{{ __('finance.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $index => $item)
                                    <tr>
                                        <td class="py-3 text-muted">{{ $index + 1 }}</td>
                                        <td class="py-3 fw-bold">{{ $item->description }}</td>
                                        <td class="py-3 text-end text-dark">{{ $currency }} {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Financial Summary -->
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="text-muted small">
                                    * {{ __('invoice.due_date') }}: <strong>{{ $invoice->due_date->format('d M, Y') }}</strong><br>
                                    * {{ __('invoice.academic_year') }}: <strong>{{ $invoice->academicSession->name ?? 'N/A' }}</strong>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <table class="table table-borderless table-sm text-end">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted">{{ __('invoice.subtotal') }}:</td>
                                            <td class="fw-bold text-dark fs-16">{{ $currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-success">{{ __('invoice.paid_to_date') }}:</td>
                                            <td class="fw-bold text-success fs-16">- {{ $currency }} {{ number_format($invoice->paid_amount, 2) }}</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="text-danger pt-3 fs-18 fw-bold">{{ __('invoice.balance_due') }}:</td>
                                            <td class="pt-3 fs-18 fw-bold text-danger">{{ $currency }} {{ number_format($dueAmount, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                @if($invoice->status != 'paid' && auth()->user()->can('payment.create'))
                                    <div class="text-end mt-3">
                                        <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-success shadow-sm btn-lg px-5">
                                            <i class="fa fa-money-bill-wave me-2"></i> {{ __('invoice.make_payment') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(!empty($onlinePayEnabled) && $paymentUrl && $invoice->status != 'paid')
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-primary bg-light">
                                    <div class="card-body">
                                        <h6 class="text-primary fw-bold mb-2"><i class="fa fa-link me-2"></i>{{ __('invoice.online_payment_link') }}</h6>
                                        <p class="text-muted small mb-3">{{ __('invoice.online_payment_help') }}</p>
                                        <div class="mb-2">
                                            <span class="text-muted small">{{ __('invoice.invoice_id_label') }}:</span>
                                            <strong>{{ $invoice->invoice_number }}</strong>
                                        </div>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" id="paymentLinkInput" value="{{ $paymentUrl }}" readonly>
                                            <button type="button" class="btn btn-primary" id="copyPaymentLinkBtn">
                                                <i class="fa fa-copy me-1"></i> {{ __('invoice.copy_link') }}
                                            </button>
                                        </div>
                                        @can('invoice.view')
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshPaymentLinkBtn" data-url="{{ route('invoices.refresh_payment_link', $invoice->id) }}">
                                            <i class="fa fa-refresh me-1"></i> {{ __('invoice.refresh_link') }}
                                        </button>
                                        @endcan
                                        <div class="mt-2 small text-muted">{{ __('online_pay.lookup_alt') }}: <a href="{{ route('pay.lookup') }}" target="_blank">{{ route('pay.lookup') }}</a></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="col-lg-12">
                <div class="card mt-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title text-primary"><i class="fa fa-history me-2"></i> {{ __('finance.payment_history') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm border-bottom">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th>{{ __('finance.payment_date') }}</th>
                                        <th>{{ __('finance.transaction_id') }}</th>
                                        <th>{{ __('finance.payment_method') }}</th>
                                        <th>{{ __('finance.recorded_by') }}</th>
                                        <th class="text-end">{{ __('finance.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td class="py-3">{{ $payment->payment_date->format('d M, Y') }}</td>
                                        <td class="py-3"><span class="badge badge-light text-dark border">{{ $payment->transaction_id }}</span></td>
                                        <td class="py-3">{{ ucfirst($payment->method) }}</td>
                                        <td class="py-3">
                                            @if($payment->source === 'online')
                                                {{ $payment->payer_name ?? __('finance.online') }}
                                            @else
                                                {{ $payment->receivedBy->name ?? __('finance.system') }}
                                            @endif
                                        </td>
                                        <td class="text-success fw-bold py-3 text-end">+ {{ $currency }} {{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4"><i class="fa fa-info-circle me-2"></i> {{ __('finance.no_payments_found') }}</td>
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

@section('js')
<script>
$(function() {
    $('#copyPaymentLinkBtn').on('click', function() {
        const input = document.getElementById('paymentLinkInput');
        if (!input) return;
        input.select();
        navigator.clipboard.writeText(input.value).then(function() {
            if (typeof toastr !== 'undefined') toastr.success(@json(__('invoice.link_copied')));
            else alert(@json(__('invoice.link_copied')));
        });
    });

    $('#refreshPaymentLinkBtn').on('click', function() {
        const url = $(this).data('url');
        if (!url || !confirm(@json(__('invoice.refresh_link') . '?'))) return;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            $('#paymentLinkInput').val(data.url);
            if (typeof toastr !== 'undefined') toastr.success(data.message);
        });
    });
});
</script>
@endsection