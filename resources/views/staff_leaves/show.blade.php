@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('staff_leave.leave_list') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('staff-leaves.index') }}" class="btn btn-light">{{ __('staff_leave.back') }}</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">{{ __('staff_leave.staff_member') }}</label>
                        <p>{{ $leave->staff->user->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">{{ __('staff_leave.status') }}</label>
                        <p><span class="badge badge-{{ $leave->status == 'approved' ? 'success' : ($leave->status == 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($leave->status) }}</span></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">{{ __('staff_leave.leave_type') }}</label>
                        <p>{{ ucfirst($leave->type) }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">{{ __('staff_leave.days') }}</label>
                        <p>{{ $leave->start_date->format('d M') }} - {{ $leave->end_date ? $leave->end_date->format('d M, Y') : 'Single Day' }}</p>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="fw-bold">{{ __('staff_leave.reason') }}</label>
                        <p class="bg-light p-3 rounded">{{ $leave->reason }}</p>
                    </div>
                    
                    @if($leave->file_path)
                    <div class="col-12">
                        <a href="{{ asset('storage/'.$leave->file_path) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa fa-paperclip"></i> {{ __('staff_leave.download') }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection