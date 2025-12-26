@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('voting.my_elections') }}</h4>
                    <p class="mb-0">{{ __('voting.active_polls') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            @forelse($elections as $election)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card">
                        <div class="card-header border-0 pb-0">
                            <h5 class="card-title">{{ $election->title }}</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted">{{ Str::limit($election->description, 100) }}</p>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <span class="badge badge-primary">{{ __('voting.closes_in') }} {{ $election->end_date->diffForHumans() }}</span>
                                <a href="{{ route('student.elections.show', $election->id) }}" class="btn btn-outline-primary btn-sm">
                                    {{ __('voting.vote_now') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">{{ __('voting.no_active_elections') }}</h4>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection