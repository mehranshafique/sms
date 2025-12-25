@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Create Subscription</h4>
                    <p class="mb-0">Manually assign a plan to an institution</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('subscriptions.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Institution</label>
                            <select name="institution_id" class="form-control default-select" required>
                                @foreach($institutions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Package Plan</label>
                            <select name="package_id" class="form-control default-select" required>
                                @foreach($packages as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="text" name="start_date" class="form-control datepicker" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control default-select">
                                <option value="active">Active (Paid)</option>
                                <option value="pending_payment">Pending Payment</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-control default-select">
                                <option value="Manual">Manual / Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Check">Check</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check custom-checkbox">
                                <input type="checkbox" class="form-check-input" id="genInv" name="generate_invoice" value="1" checked>
                                <label class="form-check-label" for="genInv">Generate Invoice automatically</label>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign Subscription</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection