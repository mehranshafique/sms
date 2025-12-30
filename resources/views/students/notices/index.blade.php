@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('notice.my_notices') }}</h4>
                    <p class="mb-0">{{ __('notice.student_notice_subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            @forelse($notices as $notice)
                <div class="col-xl-6 col-lg-12">
                    <div class="card shadow-sm mb-4 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <span class="badge badge-{{ $notice->type == 'urgent' ? 'danger' : 'info' }} light mb-2">
                                        {{ ucfirst($notice->type) }}
                                    </span>
                                    <h4 class="card-title mb-1">{{ $notice->title }}</h4>
                                </div>
                                <div class="text-end text-muted small" style="min-width: 80px;">
                                    {{-- FIX: Check if created_at exists before formatting --}}
                                    <i class="fa fa-calendar d-block mb-1"></i>
                                    @if($notice->created_at)
                                        {{ $notice->created_at->format('d M') }}<br>
                                        {{ $notice->created_at->format('Y') }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            
                            <p class="card-text text-muted mb-3">
                                {{ Str::limit(strip_tags($notice->description), 120) }}
                            </p>
                            
                            {{-- Using action helper to safely link to the controller method if route name is uncertain --}}
                            <a href="{{ action([\App\Http\Controllers\StudentNoticeController::class, 'show'], $notice->id) }}" class="btn btn-link p-0 text-primary fw-bold">
                                Read More <i class="fa fa-angle-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card text-center p-5">
                        <div class="card-body">
                            <i class="fa fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No notices found</h4>
                            <p class="text-muted">Check back later for updates from your school.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="row mt-3">
            <div class="col-12 d-flex justify-content-center">
                {{ $notices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection