@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('subscription.page_title') }}</h4>
                    <p class="mb-0">{{ __('subscription.manage_subscriptions') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('subscriptions.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('subscription.create_subscription') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('subscription.subscription_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped vertical-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('subscription.institution') }}</th>
                                        <th>{{ __('subscription.plan') }}</th>
                                        <th>{{ __('subscription.start_date') }}</th>
                                        <th>{{ __('subscription.end_date') }}</th>
                                        <th>{{ __('subscription.days_left') }}</th>
                                        <th>{{ __('subscription.status') }}</th>
                                        <th>{{ __('subscription.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($subscriptions as $sub)
                                        <tr>
                                            <td>
                                                <strong>{{ $sub->institution->name }}</strong><br>
                                                <small class="text-muted">{{ $sub->institution->code }}</small>
                                            </td>
                                            <td>{{ $sub->package->name ?? 'Custom' }}</td>
                                            <td>{{ $sub->start_date->format('d M, Y') }}</td>
                                            <td>{{ $sub->end_date->format('d M, Y') }}</td>
                                            <td>
                                                @php $days = $sub->daysLeft(); @endphp
                                                <span class="badge badge-{{ $days < 30 ? 'warning' : 'success' }}">
                                                    {{ $days }} Days
                                                </span>
                                            </td>
                                            <td>
                                                @if($sub->status == 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @elseif($sub->status == 'expired')
                                                    <span class="badge badge-danger">Expired</span>
                                                @else
                                                    <span class="badge badge-warning">{{ ucfirst($sub->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown ms-auto text-end">
                                                    <div class="btn-link" data-bs-toggle="dropdown">
                                                        <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"></rect><circle fill="#000000" cx="5" cy="12" r="2"></circle><circle fill="#000000" cx="12" cy="12" r="2"></circle><circle fill="#000000" cx="19" cy="12" r="2"></circle></g></svg>
                                                    </div>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        {{-- FIXED LINKS --}}
                                                        <a class="dropdown-item" href="{{ route('subscriptions.edit', $sub->id) }}">{{ __('subscription.edit_package') }}</a>
                                                        {{-- Renewal is essentially an edit where you extend the date --}}
                                                        <a class="dropdown-item" href="{{ route('subscriptions.edit', $sub->id) }}">Renew Subscription</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center">{{ __('subscription.no_records') }}</td></tr>
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