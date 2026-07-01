@extends('layout.layout')

@section('content')
@include('school_events.partials.styles')
<div class="content-body">
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <div class="se-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <h3 class="text-white fw-bold mb-1">{{ __('school_event.page_title') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('school_event.subtitle') }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="la la-calendar se-hero__icon d-none d-md-block"></i>
                            <a href="{{ route('school-events.create') }}" class="btn btn-light fw-bold text-primary">
                                <i class="la la-plus me-1"></i> {{ __('school_event.create') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-4 col-6">
                <div class="se-stat">
                    <div class="se-stat__icon" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);"><i class="la la-calendar"></i></div>
                    <div class="text-muted small">{{ __('school_event.stat_total') }}</div>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-sm-4 col-6">
                <div class="se-stat">
                    <div class="se-stat__icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="la la-edit"></i></div>
                    <div class="text-muted small">{{ __('school_event.stat_draft') }}</div>
                    <h4 class="fw-bold mb-0">{{ $stats['draft'] ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-sm-4 col-6">
                <div class="se-stat">
                    <div class="se-stat__icon" style="background:linear-gradient(135deg,#059669,#047857);"><i class="la la-paper-plane"></i></div>
                    <div class="text-muted small">{{ __('school_event.stat_sent') }}</div>
                    <h4 class="fw-bold mb-0">{{ $stats['sent'] ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius:15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-transparent">
                        <h4 class="card-title mb-0 fw-bold fs-18">{{ __('school_event.list_title') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive digitex-dt-wrap">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('school_event.field_name') }}</th>
                                        <th>{{ __('school_event.field_date') }}</th>
                                        <th>{{ __('school_event.field_venue') }}</th>
                                        <th>{{ __('school_event.field_audience') }}</th>
                                        <th>{{ __('school_event.field_status') }}</th>
                                        <th class="text-end">{{ __('school_event.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($events as $event)
                                    <tr>
                                        <td class="fw-bold">{{ $event->name }}</td>
                                        <td>{{ localized_date($event->event_date, 'd M Y') }}</td>
                                        <td>{{ $event->venue ?: '—' }}</td>
                                        <td>{{ __('school_event.audience_' . $event->audience) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $event->status === 'sent' ? 'success' : 'warning' }} light">
                                                {{ __('school_event.status_' . $event->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('school-events.show', $event) }}" class="btn btn-primary btn-xs shadow-sm">
                                                <i class="fa fa-eye"></i> {{ __('school_event.view') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="la la-calendar fs-1 d-block mb-2 opacity-50"></i>
                                            {{ __('school_event.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($events->hasPages())
                            <div class="mt-3">{{ $events->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
