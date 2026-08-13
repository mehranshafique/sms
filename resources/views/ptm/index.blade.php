@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('ptm.page_title') }}</h4>
                    <p class="mb-0">{{ __('ptm.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2 align-items-center">
                <form method="GET" action="{{ route('ptm.index') }}" class="d-flex gap-2">
                    <select name="scope" class="form-control default-select shadow-sm w-auto" onchange="this.form.submit()">
                        <option value="">{{ __('ptm.filter_all_scopes') }}</option>
                        @foreach(\App\Models\ParentMeeting::SCOPES as $sc)
                            <option value="{{ $sc }}" @selected(($scope ?? '') === $sc)>{{ __('ptm.scope_' . $sc) }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-control default-select shadow-sm w-auto" onchange="this.form.submit()">
                        <option value="">{{ __('ptm.filter_all') }}</option>
                        @foreach(\App\Models\ParentMeeting::STATUSES as $st)
                            <option value="{{ $st }}" @selected(($status ?? '') === $st)>{{ __('ptm.status_' . $st) }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('ptm.create') }}" class="btn btn-primary shadow-sm">
                    <i class="fa fa-plus me-2"></i>{{ __('ptm.create') }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('ptm.stat_total') }}</div>
                        <h4 class="fw-bold mb-0">{{ $stats['total'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('ptm.stat_pending') }}</div>
                        <h4 class="fw-bold mb-0 text-warning">{{ $stats['pending'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('ptm.stat_confirmed') }}</div>
                        <h4 class="fw-bold mb-0 text-primary">{{ $stats['confirmed'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ __('ptm.stat_completed') }}</div>
                        <h4 class="fw-bold mb-0 text-success">{{ $stats['completed'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 bg-transparent pt-4 px-4">
                        <h5 class="card-title mb-0 fw-bold">{{ __('ptm.list_title') }}</h5>
                        <p class="text-muted small mb-0 mt-1">{{ __('ptm.manage_on_web') }}</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('ptm.field_student') }}</th>
                                        <th>{{ __('ptm.field_scope') }}</th>
                                        <th>{{ __('ptm.field_topic') }}</th>
                                        <th>{{ __('ptm.field_date') }}</th>
                                        <th>{{ __('ptm.field_status') }}</th>
                                        <th>{{ __('ptm.field_requester') }}</th>
                                        <th class="text-end">{{ __('ptm.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($meetings as $meeting)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $meeting->student?->full_name ?? '—' }}
                                            @if($meeting->class_section_id)
                                                <div class="small text-muted">
                                                    {{ function_exists('class_section_label') && $meeting->classSection
                                                        ? class_section_label($meeting->classSection)
                                                        : ($meeting->classSection->name ?? '') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $meeting->scope === 'class' ? 'info' : 'secondary' }} light">
                                                {{ __('ptm.scope_' . ($meeting->scope ?? 'individual')) }}
                                            </span>
                                        </td>
                                        <td>{{ $meeting->topic }}</td>
                                        <td>{{ $meeting->preferred_date ? localized_date($meeting->preferred_date, 'd M Y') : '—' }}</td>
                                        <td>
                                            @php
                                                $badge = match($meeting->status) {
                                                    'confirmed' => 'primary',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $badge }} light">{{ __('ptm.status_' . $meeting->status) }}</span>
                                        </td>
                                        <td>{{ $meeting->requester?->name ?? '—' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('ptm.show', $meeting) }}" class="btn btn-primary btn-xs shadow-sm">
                                                <i class="fa fa-eye"></i> {{ __('ptm.view') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">{{ __('ptm.empty') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($meetings->hasPages())
                            <div class="mt-3">{{ $meetings->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
