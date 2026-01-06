@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('invoice.invoice_details') }}</h4>
                    <p class="mb-0">#{{ $invoice->invoice_number }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">{{ __('invoice.invoice_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">#{{ $invoice->invoice_number }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card mt-3">
                    <div class="card-header"> 
                        <strong>{{ $invoice->created_at->format('d M, Y') }}</strong> 
                        <span class="float-end">
                            <strong>{{ __('invoice.status_label') }}:</strong> 
                            <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partial' ? 'warning' : 'danger') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </span> 
                    </div>
                    <div class="card-body">
                        <div class="row mb-5">
                            {{-- LEFT ALIGNED INSTITUTION INFO --}}
                            <div class="col-xl-4 col-3 mt-4 text-start">
                                <h6>{{ __('invoice.from') }}:</h6>
                                <div> <strong>{{ $invoice->institution->name ?? 'Institution Name' }}</strong> </div>
                                <div>{{ $invoice->institution->address ?? '' }}</div>
                                <div>{{ __('invoice.email') }}: {{ $invoice->institution->email ?? '' }}</div>
                                <div>{{ __('invoice.phone') }}: {{ $invoice->institution->phone ?? '' }}</div>
                            </div>
                            <div class="col-xl-4 col-3 mt-4">
                                <h6>{{ __('invoice.to') }}:</h6>
                                <div> <strong>{{ $invoice->student->full_name }}</strong> </div>
                                <div>{{ __('invoice.id') }}: {{ $invoice->student->admission_number }}</div>
                                <div>{{ __('invoice.class') }}: {{ $invoice->student->enrollments->last()->classSection->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-xl-4 col-6 mt-4 text-end">
                                <h6>{{ __('invoice.invoice_info') }}:</h6>
                                <div>{{ __('invoice.invoice_number') }}: <strong>{{ $invoice->invoice_number }}</strong></div>
                                <div>{{ __('invoice.issue_date') }}: {{ $invoice->issue_date->format('d M, Y') }}</div>
                                <div>{{ __('invoice.due_date') }}: {{ $invoice->due_date->format('d M, Y') }}</div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="center">#</th>
                                        <th>{{ __('invoice.item_description') }}</th>
                                        <th class="right">{{ __('invoice.cost') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $index => $item)
                                    <tr>
                                        <td class="center">{{ $index + 1 }}</td>
                                        <td class="strong">{{ $item->description }}</td>
                                        <td class="right">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-lg-4 col-sm-5"> </div>
                            <div class="col-lg-4 col-sm-5 ms-auto">
                                <table class="table table-clear">
                                    <tbody>
                                        <tr>
                                            <td class="left"><strong>{{ __('invoice.subtotal') }}</strong></td>
                                            <td class="right">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($invoice->total_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong>{{ __('invoice.paid_amount') }}</strong></td>
                                            <td class="right text-success">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($invoice->paid_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong>{{ __('invoice.balance_due') }}</strong></td>
                                            <td class="right text-danger"><strong>{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end">
                            {{-- Print Button --}}
                            <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="btn btn-outline-secondary me-2">
                                <i class="fa fa-print"></i> {{ __('invoice.print') }}
                            </a>
                            
                            {{-- Download PDF Button --}}
                            <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-primary me-2">
                                <i class="fa fa-download"></i> {{ __('invoice.download_pdf') }}
                            </a>
                            
                            @if($invoice->status != 'paid')
                                @can('payment.create')
                                <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-success"><i class="fa fa-money"></i> {{ __('invoice.pay_now') }}</a>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Payment History (Same as before) --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('finance.payment_history') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('finance.date') }}</th>
                                        <th>{{ __('finance.transaction_id') }}</th>
                                        <th>{{ __('finance.method') }}</th>
                                        <th>{{ __('finance.amount') }}</th>
                                        <th>{{ __('finance.recorded_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                                        <td>{{ $payment->transaction_id }}</td>
                                        <td>{{ ucfirst($payment->method) }}</td>
                                        <td class="text-success fw-bold">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $payment->receivedBy->name ?? 'System' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('finance.no_payments_found') }}</td>
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