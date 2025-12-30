@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ $notice->title }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('student.notices.index') }}">{{ __('notice.notice_board') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('notice.details') }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <div>
                                {{-- FIX: Use created_at instead of publish_date to prevent null error --}}
                                <span class="badge badge-light text-muted mb-2">
                                    <i class="fa fa-calendar me-1"></i> 
                                    @if($notice->created_at)
                                        {{ $notice->created_at->format('l, d F Y') }}
                                    @else
                                        Date N/A
                                    @endif
                                </span>
                                @if($notice->type == 'urgent')
                                    <span class="badge badge-danger ms-2">Urgent</span>
                                @endif
                            </div>
                            <a href="{{ route('student.notices.index') }}" class="btn btn-light btn-sm"><i class="fa fa-arrow-left me-1"></i> {{ __('notice.back') }}</a>
                        </div>
                        
                        <div class="notice-content fs-16 text-dark lh-lg">
                            {{-- Use description field if content field doesn't exist, based on index view usage --}}
                            {!! nl2br(e($notice->description ?? $notice->content)) !!}
                        </div>

                        <div class="mt-5 pt-3 border-top">
                            <small class="text-muted">{{ __('notice.posted_by') }}: {{ $notice->creator->name ?? 'Admin' }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection