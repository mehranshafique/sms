@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('subscription.invoice_title') }}</h4>
                    <p class="mb-0">{{ __('subscription.manage_invoices') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('subscription.invoice_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped vertical-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('subscription.invoice_number') }}</th>
                                        <th>{{ __('subscription.institution') }}</th>
                                        <th>{{ __('subscription.plan') }}</th>
                                        <th>{{ __('subscription.amount') }}</th>
                                        <th>{{ __('subscription.issue_date') }}</th>
                                        <th>{{ __('subscription.due_date') }}</th>
                                        <th>{{ __('subscription.status') }}</th>
                                        <th class="text-end">{{ __('subscription.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $inv)
                                    <tr>
                                        <td class="fw-bold text-primary">#{{ $inv->invoice_number }}</td>
                                        <td>
                                            <strong>{{ $inv->institution->name }}</strong>
                                        </td>
                                        <td>{{ $inv->subscription->package->name ?? 'Custom' }}</td>
                                        <td class="text-black font-w600">${{ number_format($inv->total_amount, 2) }}</td>
                                        <td>{{ $inv->invoice_date->format('d M, Y') }}</td>
                                        <td>{{ $inv->due_date->format('d M, Y') }}</td>
                                        <td>
                                            @if($inv->status == 'paid')
                                                <span class="badge badge-success">{{ __('subscription.paid') }}</span>
                                            @elseif($inv->status == 'overdue')
                                                <span class="badge badge-danger">{{ __('subscription.overdue') }}</span>
                                            @else
                                                <span class="badge badge-warning">{{ ucfirst($inv->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('subscriptions.invoices.show', $inv->id) }}" class="btn btn-xs btn-info shadow me-1" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('subscriptions.invoices.download', $inv->id) }}" class="btn btn-xs btn-primary shadow" title="Download">
                                                <i class="fa fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">{{ __('subscription.no_records') }}</td>
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