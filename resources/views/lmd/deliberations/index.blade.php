@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <h4>{{ __('lmd_deliberation.page_title') }}</h4>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        @if($session)
        <form method="POST" action="{{ route('lmd-deliberations.generate') }}" class="card card-body mb-4">
            @csrf
            <input type="hidden" name="academic_session_id" value="{{ $session->id }}">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label>{{ __('lmd_deliberation.semester') }}</label>
                    <select name="semester" class="form-control">
                        <option value="1" @selected($semester === 1)>1</option>
                        <option value="2" @selected($semester === 2)>2</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary">{{ __('lmd_deliberation.generate') }}</button>
                </div>
            </div>
        </form>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('transport.student') }}</th>
                            <th>{{ __('lmd_deliberation.average') }}</th>
                            <th>{{ __('state_exam.mention') }}</th>
                            <th>{{ __('lmd_deliberation.decision') }}</th>
                            <th>{{ __('state_exam.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliberations as $d)
                            <tr>
                                <td>{{ $d->student->full_name ?? '—' }}</td>
                                <td>{{ $d->average }}/20</td>
                                <td>{{ $d->mention }}</td>
                                <td>{{ __('lmd_deliberation.decision_' . $d->decision) }}</td>
                                <td>{{ $d->status === 'validated' ? __('lmd_deliberation.status_validated') : __('lmd_deliberation.status_draft') }}</td>
                                <td>
                                    @if($d->status !== 'validated')
                                        <form method="POST" action="{{ route('lmd-deliberations.validate', $d) }}">@csrf
                                            <button class="btn btn-sm btn-success">{{ __('lmd_deliberation.validate') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('reports.no_records_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $deliberations->appends(['semester' => $semester])->links() }}
        </div>
    </div>
</div>
@endsection
