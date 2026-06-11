@extends('layout.layout')

@section('content')
<div class="content-body"><div class="container-fluid">
    <h4>{{ __('guardian.my_requests') }} — {{ $student->full_name }}</h4>
    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{ __('guardian.my_requests') }}</th><th>{{ __('state_exam.status') }}</th><th>Date</th></tr></thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>{{ ucfirst($req->type) }} — {{ \Illuminate\Support\Str::limit($req->reason, 60) }}</td>
                    <td>{{ ucfirst($req->status) }}</td>
                    <td>{{ $req->created_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted">{{ __('reports.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table></div></div>
    <a href="{{ route('guardian.index') }}" class="btn btn-light mt-3">{{ __('institute.cancel') }}</a>
</div></div>
@endsection
