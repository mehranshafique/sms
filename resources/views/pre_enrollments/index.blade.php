@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-7 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('pre_enrollment.page_title') }}</h4>
                    <p class="mb-0">{{ __('pre_enrollment.manage_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('pre-enrollments.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('pre_enrollment.new_candidate') }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(isset($messagingReady) && ! $messagingReady)
            <div class="alert alert-warning" role="alert">
                <i class="fa fa-triangle-exclamation me-2"></i>{{ __('pre_enrollment.messaging_off') }}
                @can('setting.view')
                    <a href="{{ route('configuration.index') }}" class="alert-link">{{ __('pre_enrollment.open_configuration') }}</a>
                @endcan
            </div>
        @endif

        @if(!empty($publicUrl))
        <div class="card h-auto shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1"><i class="fa fa-link me-2 text-primary"></i>{{ __('pre_enrollment.public.link_title') }}</h6>
                        <p class="text-muted small mb-2">{{ __('pre_enrollment.public.link_help') }}</p>
                        <div class="input-group" style="max-width:560px;">
                            <input type="text" class="form-control" id="publicPreEnrollUrl" value="{{ $publicUrl }}" readonly @disabled(! $publicEnabled)>
                            <button type="button" class="btn btn-outline-primary" id="copyPublicPreEnrollUrl" @disabled(! $publicEnabled)>
                                <i class="fa fa-copy me-1"></i>{{ __('pre_enrollment.public.copy') }}
                            </button>
                            <a href="{{ $publicUrl }}" target="_blank" class="btn btn-light {{ $publicEnabled ? '' : 'disabled' }}">
                                <i class="fa fa-up-right-from-square"></i>
                            </a>
                        </div>
                        @if(! $publicEnabled)
                            <small class="text-danger d-block mt-1">{{ __('pre_enrollment.public.disabled_hint') }}</small>
                        @endif
                    </div>
                    @canany(['pre_enrollment.update', 'student.update', 'student.create'])
                    <form method="POST" action="{{ route('pre-enrollments.public.toggle') }}" class="text-sm-end">
                        @csrf
                        <input type="hidden" name="enabled" value="{{ $publicEnabled ? 0 : 1 }}">
                        <button class="btn btn-{{ $publicEnabled ? 'outline-danger' : 'success' }} btn-rounded">
                            <i class="fa fa-{{ $publicEnabled ? 'lock' : 'lock-open' }} me-2"></i>
                            {{ $publicEnabled ? __('pre_enrollment.public.disable_btn') : __('pre_enrollment.public.enable_btn') }}
                        </button>
                    </form>
                    @endcanany
                </div>
            </div>
        </div>
        @endif

        {{-- STATS CARDS --}}
        <div class="row">
            @foreach([
                ['candidates', 'stats_candidates', 'primary', 'la-user-plus'],
                ['invited', 'stats_invited', 'info', 'la-envelope'],
                ['test_completed', 'stats_test_completed', 'warning', 'la-edit'],
                ['admitted', 'stats_admitted', 'success', 'la-check-circle'],
                ['not_admitted', 'stats_not_admitted', 'danger', 'la-times-circle'],
                ['finalized', 'stats_finalized', 'dark', 'la-graduation-cap'],
            ] as [$key, $label, $color, $icon])
                <div class="col-xl-2 col-lg-4 col-sm-6">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-{{ $color }} text-{{ $color }}">
                                    <i class="la {{ $icon }}"></i>
                                </span>
                                <div class="media-body">
                                    <p class="mb-1">{{ __('pre_enrollment.'.$label) }}</p>
                                    <h4 class="mb-0">{{ $stats[$key] ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('pre_enrollment.status') }}</label>
                        <select name="status" class="form-control default-select">
                            <option value="all" @selected($status==='all')>{{ __('pre_enrollment.all') }}</option>
                            @foreach(\App\Models\PreEnrollment::STATUSES as $st)
                                <option value="{{ $st }}" @selected($status===$st)>{{ __('pre_enrollment.status_'.$st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">{{ __('pre_enrollment.search') }}</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('pre_enrollment.search_placeholder') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button class="btn btn-primary btn-rounded w-100">
                            <i class="fa fa-search me-2"></i>{{ __('pre_enrollment.filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header border-0 pb-0">
                <h4 class="card-title">{{ __('pre_enrollment.candidates_list') }}</h4>
                <span class="badge badge-light">{{ $candidates->total() }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-responsive-md align-middle card-table">
                        <thead>
                            <tr>
                                <th>{{ __('pre_enrollment.temporary_id') }}</th>
                                <th>{{ __('pre_enrollment.candidate') }}</th>
                                <th>{{ __('pre_enrollment.parent') }}</th>
                                <th>{{ __('pre_enrollment.requested_class') }}</th>
                                <th>{{ __('pre_enrollment.test_at') }}</th>
                                <th>{{ __('pre_enrollment.source') }}</th>
                                <th>{{ __('pre_enrollment.status') }}</th>
                                <th class="text-end">{{ __('pre_enrollment.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($candidates as $row)
                                @php
                                    $badge = match($row->status) {
                                        \App\Models\PreEnrollment::STATUS_ADMITTED => 'success',
                                        \App\Models\PreEnrollment::STATUS_FINALIZED => 'primary',
                                        \App\Models\PreEnrollment::STATUS_INVITED => 'info',
                                        \App\Models\PreEnrollment::STATUS_TEST_COMPLETED => 'warning',
                                        \App\Models\PreEnrollment::STATUS_NOT_ADMITTED => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="badge badge-light text-dark">{{ $row->temporary_id }}</span></td>
                                    <td>
                                        <a href="{{ route('pre-enrollments.show', $row) }}" class="fw-bold text-primary">{{ $row->fullName() }}</a>
                                        <div class="small text-muted">
                                            {{ $row->gender ? __('pre_enrollment.gender_'.strtolower($row->gender)) : '' }}
                                            @if($row->dob) · {{ localized_date($row->dob, 'd M Y') }} @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ $row->parent_name ?: '—' }}
                                        <div class="small text-muted">{{ $row->parent_phone }}</div>
                                    </td>
                                    <td>
                                        {{ $row->requestedClassSection->name ?? $row->requestedGradeLevel->name ?? '—' }}
                                        @if($row->requested_option)
                                            <div class="small text-muted">{{ $row->requested_option }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->test_at)
                                            {{ localized_date($row->test_at, 'd M Y H:i') }}
                                            @if($row->test_at->isFuture() && $row->status === \App\Models\PreEnrollment::STATUS_INVITED)
                                                <div class="small text-info">{{ __('pre_enrollment.upcoming') }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><span class="text-muted">{{ ucfirst($row->source) }}</span></td>
                                    <td><span class="badge badge-{{ $badge }} light">{{ $row->statusLabel() }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('pre-enrollments.show', $row) }}" class="btn btn-primary btn-xs btn-rounded">
                                            <i class="fa fa-eye me-1"></i>{{ __('pre_enrollment.view') }}
                                        </a>
                                        @if($row->status !== \App\Models\PreEnrollment::STATUS_FINALIZED)
                                            <a href="{{ route('pre-enrollments.edit', $row) }}" class="btn btn-light btn-xs btn-rounded">
                                                <i class="fa fa-pen"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="la la-user-plus text-muted" style="font-size:42px;"></i>
                                        <p class="text-muted mb-0 mt-2">{{ __('pre_enrollment.no_records') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $candidates->links() }}
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('copyPublicPreEnrollUrl');
    const input = document.getElementById('publicPreEnrollUrl');
    if (!btn || !input) return;
    btn.addEventListener('click', async function () {
        try {
            await navigator.clipboard.writeText(input.value);
            btn.innerHTML = '<i class="fa fa-check me-1"></i>{{ __('pre_enrollment.public.copied') }}';
            setTimeout(() => {
                btn.innerHTML = '<i class="fa fa-copy me-1"></i>{{ __('pre_enrollment.public.copy') }}';
            }, 1500);
        } catch (e) {
            input.select();
            document.execCommand('copy');
        }
    });
});
</script>
@endsection
