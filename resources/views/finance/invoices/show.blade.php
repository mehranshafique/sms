@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('invoice.invoice_details') ?? 'Invoice Details' }}</h4>
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
                            <strong>Status:</strong> 
                            <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partial' ? 'warning' : 'danger') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </span> 
                    </div>
                    <div class="card-body">
                        <div class="row mb-5">
                            {{-- LEFT ALIGNED INSTITUTION INFO --}}
                            <div class="col-xl-4 col-3 mt-4 text-start">
                                <h6>From:</h6>
                                <div> <strong>{{ $invoice->institution->name ?? 'Institution Name' }}</strong> </div>
                                <div>{{ $invoice->institution->address ?? '' }}</div>
                                <div>Email: {{ $invoice->institution->email ?? '' }}</div>
                                <div>Phone: {{ $invoice->institution->phone ?? '' }}</div>
                            </div>
                            <div class="col-xl-4 col-3 mt-4">
                                <h6>To:</h6>
                                <div> <strong>{{ $invoice->student->full_name }}</strong> </div>
                                <div>ID: {{ $invoice->student->admission_number }}</div>
                                <div>Class: {{ $invoice->student->enrollments->last()->classSection->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-xl-4 col-6 mt-4 text-end">
                                <h6>Invoice Info:</h6>
                                <div>Invoice #: <strong>{{ $invoice->invoice_number }}</strong></div>
                                <div>Issue Date: {{ $invoice->issue_date->format('d M, Y') }}</div>
                                <div>Due Date: {{ $invoice->due_date->format('d M, Y') }}</div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="center">#</th>
                                        <th>Item Description</th>
                                        <th class="right">Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $index => $item)
                                    <tr>
                                        <td class="center">{{ $index + 1 }}</td>
                                        <td class="strong">{{ $item->description }}</td>
                                        <td class="right">{{ number_format($item->amount, 2) }}</td>
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
                                            <td class="left"><strong>Subtotal</strong></td>
                                            <td class="right">{{ number_format($invoice->total_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong>Paid Amount</strong></td>
                                            <td class="right text-success">{{ number_format($invoice->paid_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong>Balance Due</strong></td>
                                            <td class="right text-danger"><strong>{{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</strong></td>
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
                                <i class="fa fa-print"></i> Print
                            </a>
                            
                            {{-- Download PDF Button --}}
                            <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-primary me-2">
                                <i class="fa fa-download"></i> Download PDF
                            </a>
                            
                            @if($invoice->status != 'paid')
                                @can('payment.create')
                                <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-success"><i class="fa fa-money"></i> Pay Now</a>
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
                        <h4 class="card-title">Payment History</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Transaction ID</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                                        <td>{{ $payment->transaction_id }}</td>
                                        <td>{{ ucfirst($payment->method) }}</td>
                                        <td class="text-success fw-bold">{{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $payment->receivedBy->name ?? 'System' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No payments recorded yet.</td>
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