@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('subscription.invoice_title') }}</h4>
                    <p class="mb-0">#{{ $invoice->invoice_number }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('subscriptions.invoices') }}" class="btn btn-secondary btn-rounded me-2">Back</a>
                <a href="{{ route('subscriptions.invoices.print', $invoice->id) }}" target="_blank" class="btn btn-outline-primary btn-rounded me-2">
                    <i class="fa fa-print"></i> {{ __('subscription.print') }}
                </a>
                <a href="{{ route('subscriptions.invoices.download', $invoice->id) }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-download"></i> {{ __('subscription.download') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        
                        {{-- Header with Logo --}}
                        <div class="row mb-5">
                            <div class="col-xl-6 col-md-6 mb-4">
                                <img src="https://e-digitex.com/public/images/smsslogonew.png" style="width: 150px; margin-bottom: 15px;" alt="Logo">
                                <h4 class="text-primary">{{ __('subscription.platform_invoice') }}</h4>
                            </div>
                            <div class="col-xl-6 col-md-6 mb-4 text-end">
                                <h3>{{ __('subscription.invoice_number') }}: <span class="text-black">{{ $invoice->invoice_number }}</span></h3>
                                <div>{{ __('subscription.issue_date') }}: {{ $invoice->invoice_date->format('d M, Y') }}</div>
                                <div>{{ __('subscription.due_date') }}: {{ $invoice->due_date->format('d M, Y') }}</div>
                                <div class="mt-2">
                                    <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : 'danger' }} badge-lg">
                                        {{ ucfirst(__($invoice->status)) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- From / To --}}
                        <div class="row mb-5">
                            <div class="col-xl-6 col-md-6 mb-4">
                                <h6 class="text-uppercase text-muted">{{ __('subscription.from') }}</h6>
                                <div><strong>Digitex System</strong></div>
                                <div>support@digitex.com</div>
                            </div>
                            <div class="col-xl-6 col-md-6 mb-4 text-end">
                                <h6 class="text-uppercase text-muted">{{ __('subscription.bill_to') }}</h6>
                                <div><strong>{{ $invoice->institution->name }}</strong></div>
                                <div>{{ $invoice->institution->address }}</div>
                                <div>{{ $invoice->institution->city }}</div>
                                <div>{{ $invoice->institution->email }}</div>
                            </div>
                        </div>
                        
                        {{-- Table --}}
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('subscription.description') }}</th>
                                        <th>{{ __('subscription.plan') }}</th>
                                        <th>{{ __('subscription.duration') }}</th>
                                        <th class="text-end">{{ __('subscription.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ __('subscription.platform_invoice') }}</td>
                                        <td>{{ $invoice->subscription->package->name ?? 'Custom' }}</td>
                                        <td>
                                            {{ $invoice->subscription->start_date->format('M Y') }} - 
                                            {{ $invoice->subscription->end_date->format('M Y') }}
                                        </td>
                                        <td class="text-end">${{ number_format($invoice->total_amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals --}}
                        <div class="row">
                            <div class="col-lg-4 col-sm-5 ms-auto">
                                <table class="table table-clear">
                                    <tbody>
                                        <tr>
                                            <td class="left"><strong>{{ __('subscription.subtotal') }}</strong></td>
                                            <td class="text-end">${{ number_format($invoice->total_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong>{{ __('subscription.paid') }}</strong></td>
                                            <td class="text-end">${{ number_format($invoice->subscription->price_paid ?? 0, 2) }}</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td class="left"><strong>{{ __('subscription.total') }}</strong></td>
                                            <td class="text-end"><strong>${{ number_format($invoice->total_amount, 2) }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Footer Note --}}
                        <div class="mt-5 text-center text-muted">
                            <p>{{ __('subscription.thank_you') }}</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection