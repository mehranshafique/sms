@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <h4>{{ __('guardian.welcome') }}</h4>
        <div class="row">
            @forelse($children as $child)
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5>{{ $child->full_name }}</h5>
                            <p class="text-muted small">{{ $child->admission_number }}</p>
                            <a href="{{ route('guardian.fees', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block mb-2">{{ __('guardian.my_fees') }}</a>
                            <a href="{{ route('guardian.results', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block mb-2">{{ __('guardian.my_results') }}</a>
                            <a href="{{ route('guardian.attendance', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block mb-2">{{ __('guardian.my_attendance') }}</a>
                            <a href="{{ route('guardian.requests', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block">{{ __('guardian.my_requests') }}</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-warning">{{ __('guardian.no_children') }}</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
