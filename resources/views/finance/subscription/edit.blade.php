@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('subscription.edit_package') }}</h4> {{-- Or 'Edit Subscription' --}}
                    <p class="mb-0">{{ __('subscription.institution') }}: <strong>{{ $subscription->institution->name }}</strong></p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('subscriptions.index') }}">{{ __('subscription.subscription_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">Edit</a></li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('subscriptions.update', $subscription->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.plan') }}</label>
                            <select name="package_id" class="form-control default-select" required>
                                @foreach($packages as $id => $name)
                                    <option value="{{ $id }}" {{ $subscription->package_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.status') }}</label>
                            <select name="status" class="form-control default-select">
                                <option value="active" {{ $subscription->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="pending_payment" {{ $subscription->status == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                                <option value="expired" {{ $subscription->status == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="cancelled" {{ $subscription->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.start_date') }}</label>
                            <input type="text" name="start_date" class="form-control datepicker" required value="{{ $subscription->start_date->format('Y-m-d') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.end_date') }}</label>
                            <input type="text" name="end_date" class="form-control datepicker" required value="{{ $subscription->end_date->format('Y-m-d') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subscription.payment_method') }}</label>
                            <select name="payment_method" class="form-control default-select">
                                <option value="Manual" {{ $subscription->payment_method == 'Manual' ? 'selected' : '' }}>Manual / Cash</option>
                                <option value="Bank Transfer" {{ $subscription->payment_method == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="Check" {{ $subscription->payment_method == 'Check' ? 'selected' : '' }}>Check</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('subscription.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="3">{{ $subscription->notes }}</textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">{{ __('subscription.update') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection