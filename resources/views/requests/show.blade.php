@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('requests.request_details') }}</h4>
                    <p class="mb-0">{{ $request->ticket_number }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('requests.index') }}" class="btn btn-secondary">
                    {{ __('requests.back') }}
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold d-block">{{ __('requests.applicant') }}</label>
                        <span>{{ $request->student ? $request->student->full_name : ($request->staff ? $request->staff->user->name : '-') }}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold d-block">{{ __('requests.status') }}</label>
                        @php $badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger']; @endphp
                        <span class="badge badge-{{ $badges[$request->status] }}">{{ __('requests.status_' . $request->status) }}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold d-block">{{ __('requests.request_type') }}</label>
                        <span>{{ __('requests.type_' . $request->type) }}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold d-block">{{ __('requests.date_submitted') }}</label>
                        <span>{{ $request->created_at->format('d M, Y H:i') }}</span>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="fw-bold d-block">{{ __('requests.reason') }}</label>
                        <p class="p-3 bg-light rounded">{{ $request->reason }}</p>
                    </div>
                    @if($request->file_path)
                    <div class="col-12">
                        <a href="{{ asset('storage/' . $request->file_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-paperclip me-2"></i> {{ __('requests.download_attachment') }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection