@php
    $periodStates = $periodStates ?? [];
    $termCloseStatus = $termCloseStatus ?? [];
    $academicSession = $academicSession ?? null;
@endphp

<div class="col-md-12 mb-4">
    <h5 class="text-primary border-bottom pb-2">{{ __('settings.assessment_periods_title') }}</h5>
    <p class="text-muted small">{{ __('settings.assessment_periods_help') }}</p>

    @if(!$academicSession)
        <div class="alert alert-warning mb-0">{{ __('settings.no_current_session') }}</div>
    @else
        @if(!empty($termCloseStatus))
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($termCloseStatus as $termKey => $closed)
                    <span class="badge {{ $closed ? 'bg-success' : 'bg-secondary' }}">
                        {{ strtoupper(str_replace('_', ' ', $termKey)) }}:
                        {{ $closed ? __('settings.period_status_closed') : __('settings.period_status_open') }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead>
                    <tr>
                        <th>{{ __('settings.period_label') }}</th>
                        <th>{{ __('settings.period_status') }}</th>
                        <th>{{ __('settings.closes_at') }}</th>
                        <th>{{ __('settings.closed_at') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($periodStates as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['label'] }}</strong>
                                @if($row['reopen_reason'])
                                    <div class="small text-muted">{{ $row['reopen_reason'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if($row['status'] === 'closed')
                                    <span class="badge bg-success">{{ __('settings.period_status_closed') }}</span>
                                @elseif($row['status'] === 'reopened')
                                    <span class="badge bg-warning text-dark">{{ __('settings.period_status_reopened') }}</span>
                                @else
                                    <span class="badge bg-info">{{ __('settings.period_status_open') }}</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('settings.assessment_periods.schedule') }}" method="POST" class="d-flex gap-1">
                                    @csrf
                                    <input type="hidden" name="period_key" value="{{ $row['key'] }}">
                                    <input type="datetime-local" name="closes_at" class="form-control form-control-sm"
                                           value="{{ $row['closes_at'] ? $row['closes_at']->format('Y-m-d\\TH:i') : '' }}">
                                    <button class="btn btn-xs btn-outline-secondary" type="submit">{{ __('settings.save_schedule') }}</button>
                                </form>
                            </td>
                            <td class="small">
                                {{ $row['closed_at'] ? $row['closed_at']->format('d/m/Y H:i') : '—' }}
                                @if($row['closed_by'])
                                    <div class="text-muted">{{ $row['closed_by'] }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($row['status'] !== 'closed')
                                    <form action="{{ route('settings.assessment_periods.close') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="period_key" value="{{ $row['key'] }}">
                                        <button class="btn btn-xs btn-primary" type="submit">{{ __('settings.close_period') }}</button>
                                    </form>
                                @endif
                                @if($row['status'] === 'closed')
                                    <form action="{{ route('settings.assessment_periods.reopen') }}" method="POST" class="d-inline" onsubmit="return (function(f){ var r=prompt(@json(__('settings.reopen_reason_prompt'))); if(!r){return false;} f.querySelector('[name=reopen_reason]').value=r; return true; })(this);">
                                        @csrf
                                        <input type="hidden" name="period_key" value="{{ $row['key'] }}">
                                        <input type="hidden" name="reopen_reason" value="">
                                        <button class="btn btn-xs btn-warning" type="submit">{{ __('settings.reopen_period') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
