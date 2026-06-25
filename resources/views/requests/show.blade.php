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
                    <i class="fa fa-arrow-left me-1"></i> {{ __('requests.back') }}
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold d-block text-uppercase small text-muted">{{ __('requests.applicant') }}</label>
                        <span class="fs-16 text-dark">{{ $request->student ? $request->student->full_name : ($request->staff ? $request->staff->user->name : '-') }}</span>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold d-block text-uppercase small text-muted">{{ __('requests.status') }}</label>
                        @php 
                            // FIXED: Added 'partially_approved' to the color map to prevent "Undefined array key" crashes
                            $badges = [
                                'pending' => 'warning', 
                                'approved' => 'success', 
                                'partially_approved' => 'info', 
                                'rejected' => 'danger'
                            ]; 
                            $statusClass = $badges[$request->status] ?? 'secondary';
                            
                            // Safe translation fallback
                            $statusText = __('requests.status_' . $request->status);
                            if ($statusText === 'requests.status_' . $request->status) {
                                $statusText = ucfirst(str_replace('_', ' ', $request->status));
                            }
                        @endphp
                        <span class="badge badge-{{ $statusClass }} px-3 py-2 fs-14">{{ $statusText }}</span>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold d-block text-uppercase small text-muted">{{ __('requests.request_type') }}</label>
                        <span class="fs-16 text-dark">{{ $request->typeLabel() }}</span>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold d-block text-uppercase small text-muted">{{ __('requests.date_submitted') }}</label>
                        <span class="fs-16 text-dark">{{ $request->created_at->format('d M, Y H:i') }}</span>
                    </div>
                    
                    <div class="col-12 mb-4">
                        <label class="fw-bold d-block text-uppercase small text-muted">{{ __('requests.reason') }}</label>
                        <p class="p-3 bg-light rounded text-dark border">{{ $request->localizedReason() }}</p>
                    </div>
                    
                    @if($request->admin_note)
                    <div class="col-12 mb-4">
                        <label class="fw-bold d-block text-primary text-uppercase small"><i class="fa fa-reply me-1"></i> {{ __('requests.admin_note') ?? 'Admin Response Note' }}</label>
                        <p class="p-3 bg-primary-light border-start border-4 border-primary rounded text-dark shadow-sm">
                            {{ $request->admin_note }}
                        </p>
                        <small class="text-muted">
                            <i class="fa fa-user-check me-1"></i> {{ __('requests.processed_by') ?? 'Processed by:' }} <strong>{{ $request->approver->name ?? __('requests.admin') ?? 'Admin' }}</strong> 
                            {{ __('reports.on_date') ?? 'on' }} {{ $request->approved_at ? $request->approved_at->format('d M, Y H:i') : '' }}
                        </small>
                    </div>
                    @endif

                    @if($request->file_path)
                    <div class="col-12 mt-2 border-top pt-4">
                        <a href="{{ asset('storage/' . $request->file_path) }}" target="_blank" class="btn btn-outline-primary shadow-sm">
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