@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-3">
            <div class="col-12 text-center">
                <h4>{{ __('voting.my_elections') }}</h4>
                <p class="text-muted">{{ __('voting.active_polls') }}</p>
            </div>
        </div>

        <div class="row">
            @forelse($open as $election)
                @php $done = in_array($election->id, $participatedIds, true); @endphp
                <div class="col-md-6 col-xl-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5>{{ $election->title }}</h5>
                            @if($election->cycle)
                                <div class="small text-muted mb-2">{{ $election->cycle->title }}</div>
                            @endif
                            <div class="small mb-3">{{ __('voting.closes_in') }}: {{ $election->end_date?->format('Y-m-d H:i') }}</div>
                            @if($done)
                                <span class="badge badge-success">{{ __('voting.participation_recorded') }}</span>
                            @else
                                <a href="{{ route('student.elections.show', $election) }}" class="btn btn-primary btn-sm">{{ __('voting.vote_now') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-light text-center">{{ __('voting.no_active_elections') }}</div></div>
            @endforelse
        </div>

        @if($publishedResults->isNotEmpty())
            <h5 class="mt-4">{{ __('voting.published_results') }}</h5>
            <div class="row">
                @foreach($publishedResults as $election)
                    <div class="col-md-4 mb-2">
                        <a href="{{ route('student.elections.results', $election) }}" class="btn btn-outline-primary btn-sm w-100">{{ $election->title }}</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
