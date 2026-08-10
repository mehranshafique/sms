@extends('layout.layout')

@section('styles')
<link href="{{asset('vendor/clockpicker/css/bootstrap-clockpicker.min.css')}}" rel="stylesheet">
@include('attendance.partials.terminal_theme')
<style>
    .att-roster-row {
        grid-template-columns: minmax(180px, 1.3fr) minmax(260px, 1.4fr) 120px 120px;
    }
    @media (max-width: 991px) {
        .att-roster-row { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('staff.mark_staff_attendance') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 text-sm-end">
                <a href="{{ route('staff-attendance.analytics') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-chart-line me-1"></i>{{ __('attendance.staff_analytics_title') }}
                </a>
            </div>
        </div>

        <div class="att-terminal mb-4">
            <div class="att-edge"></div>
            <div class="att-header">
                <div>
                    <div class="att-kicker">{{ __('attendance.access_control') }}</div>
                    <h4 class="mb-0 fw-bold">{{ __('staff.mark_staff_attendance') }}</h4>
                </div>
                <div class="att-live">{{ __('attendance.live_session') }}</div>
            </div>
            <div class="att-body">
                <form action="{{ route('staff-attendance.create') }}" method="GET" class="mb-0">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('staff.date') }}</label>
                            <input type="text" name="date" class="form-control datepicker" value="{{ $date }}" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-scan w-100">
                                <i class="fa fa-id-card me-2"></i>{{ __('staff.fetch_staff') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <form action="{{ route('staff-attendance.store') }}" method="POST" class="ajax-form">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">

            <div class="att-terminal">
                <div class="att-edge"></div>
                <div class="att-header">
                    <div>
                        <div class="att-kicker">{{ __('attendance.staff_roster') }}</div>
                        <h4 class="mb-0 fw-bold">{{ __('attendance.roster_terminal') }}</h4>
                    </div>
                </div>
                <div class="att-body">
                    @forelse($staffMembers as $staff)
                        @php
                            $record = $attendance[$staff->id] ?? null;
                            $status = $record ? $record->status : 'present';
                            $in = $record ? ($record->check_in ? $record->check_in->format('H:i') : '') : '';
                            $out = $record ? ($record->check_out ? $record->check_out->format('H:i') : '') : '';
                            $staffName = $staff->user->name ?? $staff->employee_id;
                        @endphp
                        <div class="att-roster-row is-{{ $status }}" data-row>
                            <div>
                                <div class="att-name">{{ $staffName }}</div>
                                <div class="att-meta">{{ $staff->employee_id }} · {{ $staff->designation }}</div>
                            </div>
                            <div class="att-status-group">
                                @foreach(['present', 'absent', 'late', 'excused', 'half_day'] as $s)
                                    <div class="att-chip">
                                        <input type="radio"
                                               class="status-radio"
                                               name="attendance[{{ $staff->id }}][status]"
                                               value="{{ $s }}"
                                               id="st_{{ $staff->id }}_{{ $s }}"
                                               {{ $status == $s ? 'checked' : '' }}>
                                        <label for="st_{{ $staff->id }}_{{ $s }}" data-s="{{ $s }}">{{ __('staff.'.$s) }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div>
                                <label class="form-label mb-1">{{ __('staff.check_in') }}</label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="attendance[{{ $staff->id }}][check_in]" class="form-control" value="{{ $in }}" placeholder="09:00">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                            </div>
                            <div>
                                <label class="form-label mb-1">{{ __('staff.check_out') }}</label>
                                <div class="input-group clockpicker">
                                    <input type="text" name="attendance[{{ $staff->id }}][check_out]" class="form-control" value="{{ $out }}" placeholder="17:00">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">{{ __('staff.no_staff_found') }}</div>
                    @endforelse

                    @if($staffMembers->isNotEmpty())
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-scan btn-lg px-5">
                                <i class="fa fa-check me-2"></i>{{ __('staff.save_attendance') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/clockpicker/js/bootstrap-clockpicker.min.js') }}"></script>
<script>
    $(document).ready(function() {
        if($.fn.datepicker) {
            $('.datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
        }
        if($.fn.clockpicker) {
            $('.clockpicker').clockpicker({ autoclose: true });
        }

        function syncRow($input) {
            const $row = $input.closest('[data-row]');
            $row.removeClass('is-present is-absent is-late is-excused is-half_day');
            $row.addClass('is-' + $input.val());
        }
        $(document).on('change', '.status-radio', function(){ syncRow($(this)); });
        $('.status-radio:checked').each(function(){ syncRow($(this)); });
    });
</script>
@endsection
