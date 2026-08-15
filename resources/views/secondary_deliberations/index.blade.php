@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <h4>{{ __('secondary_deliberation.page_title') }}</h4>
                <p class="mb-0 text-muted">{{ __('secondary_deliberation.subtitle') }}</p>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        @if($session)
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <strong>{{ $session->name }}</strong>
                    <div class="small text-muted">{{ __('secondary_deliberation.generate_help') }}</div>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('secondary-deliberations.generate') }}">
                        @csrf
                        <button class="btn btn-primary">{{ __('secondary_deliberation.generate') }}</button>
                    </form>
                    <form method="POST" action="{{ route('secondary-deliberations.confirm') }}" onsubmit="return confirm(@json(__('secondary_deliberation.confirm_prompt')));">
                        @csrf
                        <button class="btn btn-success">{{ __('secondary_deliberation.confirm_notify') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('secondary-deliberations.decisions') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('secondary_deliberation.student') }}</th>
                                    <th>{{ __('secondary_deliberation.class') }}</th>
                                    <th>{{ __('secondary_deliberation.average') }}</th>
                                    <th>{{ __('secondary_deliberation.failed_subjects') }}</th>
                                    <th>{{ __('secondary_deliberation.decision') }}</th>
                                    <th>{{ __('secondary_deliberation.notified_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deliberations as $index => $row)
                                    <tr>
                                        <td>{{ $row->student->full_name ?? trim(($row->student->first_name ?? '') . ' ' . ($row->student->last_name ?? '')) }}</td>
                                        <td>{{ class_section_label($row->classSection) }}</td>
                                        <td>{{ number_format((float) $row->average_percentage, 1) }}%</td>
                                        <td>{{ implode(', ', $row->failed_subjects ?? []) }}</td>
                                        <td>
                                            <input type="hidden" name="decisions[{{ $index }}][id]" value="{{ $row->id }}">
                                            <select name="decisions[{{ $index }}][decision]" class="form-control form-control-sm">
                                                @foreach(['pending', 'admitted', 'repechage', 'adjourned'] as $decision)
                                                    <option value="{{ $decision }}" @selected($row->decision === $decision)>{{ __('secondary_deliberation.decision_' . $decision) }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>{{ $row->notified_at ? $row->notified_at->format('d/m/Y H:i') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('secondary_deliberation.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($deliberations->isNotEmpty())
                        <div class="mt-3 text-end">
                            <button class="btn btn-secondary">{{ __('secondary_deliberation.save_decisions') }}</button>
                        </div>
                    @endif
                </div>
            </div>
        </form>
        @else
            <div class="alert alert-warning">{{ __('settings.no_current_session') }}</div>
        @endif
    </div>
</div>
@endsection
