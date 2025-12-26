@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('notice.notice_board') }}</h4>
                    <p class="mb-0">{{ __('notice.latest_announcements') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            @forelse($notices as $notice)
                <div class="col-xl-6 col-lg-6 col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1 text-primary">{{ $notice->title }}</h5>
                                <small class="text-muted"><i class="fa fa-clock-o me-1"></i> {{ $notice->publish_date->format('d M, Y') }}</small>
                            </div>
                            <div>
                                @if($notice->type == 'urgent')
                                    <span class="badge badge-danger">Urgent</span>
                                @elseif($notice->type == 'event')
                                    <span class="badge badge-success">Event</span>
                                @else
                                    <span class="badge badge-info">Info</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text">{{ Str::limit(strip_tags($notice->content), 150) }}</p>
                            <a href="{{ route('student.notices.show', $notice->id) }}" class="btn btn-outline-primary btn-sm mt-3">{{ __('notice.read_more') }}</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fa fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">{{ __('notice.no_notices') }}</h4>
                        </div>
                    </div>
                </div>
            @endforelse
            
            <div class="col-12">
                {{ $notices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection