@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('assignment.page_title') }}</h4>
                    <p class="mb-0">{{ __('assignment.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            @forelse($assignments as $assignment)
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                    <div class="card overflow-hidden">
                        <div class="card-body">
                            <div class="text-center">
                                <div class="profile-photo">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 70px; height: 70px; font-size: 24px;">
                                        {{ substr($assignment->subject->name, 0, 1) }}
                                    </div>
                                </div>
                                <h3 class="mt-4 mb-1">{{ $assignment->subject->name }}</h3>
                                <p class="text-muted">{{ $assignment->teacher->user->name ?? 'Admin' }}</p>
                                
                                <div class="row text-start mt-4">
                                    <div class="col-12 mb-2">
                                        <h5 class="f-w-500">{{ $assignment->title }} <span class="badge badge-{{ $assignment->deadline < now() ? 'danger' : 'success' }} float-end">{{ $assignment->deadline->format('d M') }}</span></h5>
                                    </div>
                                    <div class="col-12">
                                        <p class="fs-12">{{ Str::limit($assignment->description, 100) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer pt-0 pb-0 text-center">
                            <div class="row">
                                <div class="col-12 pt-3 pb-3">
                                    @if($assignment->file_path)
                                        <a href="{{ asset('storage/'.$assignment->file_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fa fa-download me-2"></i> {{ __('assignment.attachment_view') }}
                                        </a>
                                    @else
                                        <button disabled class="btn btn-light btn-sm w-100">{{ __('assignment.no_assignments') }}</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">{{ __('assignment.no_assignments') }}</div>
                </div>
            @endforelse
        </div>
        
        <div class="row">
            <div class="col-12">
                {{ $assignments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection