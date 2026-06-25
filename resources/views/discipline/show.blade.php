@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('discipline.details') }}</h4>
                    <p class="mb-0 text-primary fw-bold">{{ $record->reference_no }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('discipline.index') }}" class="btn btn-light">{{ __('budget.cancel') }}</a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $record->title }}</h5>
                        <span class="badge badge-{{ $record->status === 'active' ? 'warning' : ($record->status === 'resolved' ? 'success' : 'secondary') }}">{{ $record->statusLabel() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="text-muted mb-1 small text-uppercase">{{ __('discipline.student') }}</p>
                                <h6>{{ $record->student->full_name ?? 'N/A' }}</h6>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1 small text-uppercase">{{ __('discipline.incident_type') }}</p>
                                <h6>{{ $record->typeLabel() }}</h6>
                            </div>
                            <div class="col-md-6 mt-3">
                                <p class="text-muted mb-1 small text-uppercase">{{ __('discipline.incident_date') }}</p>
                                <h6>{{ $record->incident_date->format('d M, Y') }}</h6>
                            </div>
                            <div class="col-md-6 mt-3">
                                <p class="text-muted mb-1 small text-uppercase">{{ __('discipline.severity') }}</p>
                                <h6>{{ $record->severityLabel() }}</h6>
                            </div>
                        </div>
                        @if($record->description)
                        <hr>
                        <p class="text-muted mb-1 small text-uppercase">{{ __('discipline.description') }}</p>
                        <p>{{ $record->description }}</p>
                        @endif
                        @if($record->action_taken)
                        <hr>
                        <p class="text-muted mb-1 small text-uppercase">{{ __('discipline.action_taken') }}</p>
                        <p>{{ $record->action_taken }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">{{ __('discipline.recorded_by') }}</p>
                        <p class="fw-bold">{{ $record->recorder->name ?? 'N/A' }}</p>
                        <p class="text-muted mb-1 small mt-3">{{ __('discipline.parents_notified') }}</p>
                        <p>
                            @if($record->parents_notified_at)
                                <span class="badge badge-success">{{ $record->parents_notified_at->format('d/m/Y H:i') }}</span>
                            @else
                                <span class="text-muted">{{ __('discipline.parents_not_notified') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
