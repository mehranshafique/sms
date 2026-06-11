@extends('layout.layout')

@section('content')
<div class="content-body"><div class="container-fluid">
    <h4>{{ __('guardian.my_results') }} — {{ $student->full_name }}</h4>
    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{ __('dashboard.subject') }}</th><th>{{ __('lmd.grade') }}</th><th>{{ __('reports.page_title') }}</th></tr></thead>
        <tbody>
            @forelse($records as $r)
                <tr>
                    <td>{{ $r->subject->name ?? '—' }}</td>
                    <td>{{ $r->marks_obtained }}</td>
                    <td>{{ $r->exam->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted">{{ __('reports.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table></div></div>
    <a href="{{ route('guardian.index') }}" class="btn btn-light mt-3">{{ __('institute.cancel') }}</a>
</div></div>
@endsection
