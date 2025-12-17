@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('head_officers.officer_details') }}</h4>
                    <p class="mb-0">{{ $head_officer->name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('header-officers.index') }}">{{ __('head_officers.officer_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $head_officer->name }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="profile-photo">
                                @if($head_officer->profile_picture)
                                    <img src="{{ asset('storage/' . $head_officer->profile_picture) }}" width="100" class="img-fluid rounded-circle" alt="Profile">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 40px; font-weight: bold;">
                                        {{ substr($head_officer->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <h3 class="mt-4 mb-1">{{ $head_officer->name }}</h3>
                            <p class="text-muted">
                                @if($head_officer->roles->isNotEmpty())
                                    {{ $head_officer->roles->pluck('name')->join(', ') }}
                                @else
                                    {{ __('head_officers.head_officer') }}
                                @endif
                            </p>
                            
                            <div class="d-flex justify-content-center mt-4">
                                <a href="{{ route('header-officers.edit', $head_officer->id) }}" class="btn btn-primary btn-sm me-2">
                                    <i class="fa fa-pencil me-1"></i> {{ __('head_officers.edit') }}
                                </a>
                                @if($head_officer->phone)
                                    <a href="tel:{{ $head_officer->phone }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fa fa-phone me-1"></i> {{ __('head_officers.call') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-top">
                            <h4 class="card-title">{{ __('head_officers.assigned_institutes') }}</h4>
                            <div class="d-flex flex-wrap mt-3">
                                @forelse($head_officer->institutes as $institute)
                                    <span class="badge badge-outline-primary me-2 mb-2">{{ $institute->name }}</span>
                                @empty
                                    <span class="text-muted small">{{ __('head_officers.no_institutes_assigned') }}</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-8 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('head_officers.profile_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-about-me">
                            <div class="pt-4 border-bottom-1 pb-4">
                                <h4 class="text-primary mb-4">{{ __('head_officers.contact_information') }}</h4>
                                <div class="row mb-2">
                                    <div class="col-sm-3 col-5">
                                        <h5 class="f-w-500">{{ __('head_officers.email') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-9 col-7"><span>{{ $head_officer->email }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-3 col-5">
                                        <h5 class="f-w-500">{{ __('head_officers.phone') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-9 col-7"><span>{{ $head_officer->phone ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-3 col-5">
                                        <h5 class="f-w-500">{{ __('head_officers.address') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-9 col-7"><span>{{ $head_officer->address ?? 'N/A' }}</span></div>
                                </div>
                            </div>
                            <div class="pt-4 border-bottom-1 pb-4">
                                <h4 class="text-primary mb-4">{{ __('head_officers.account_status') }}</h4>
                                <div class="row mb-2">
                                    <div class="col-sm-3 col-5">
                                        <h5 class="f-w-500">{{ __('head_officers.status') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-9 col-7">
                                        @if($head_officer->is_active)
                                            <span class="badge badge-success">{{ __('head_officers.active') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ __('head_officers.inactive') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-3 col-5">
                                        <h5 class="f-w-500">{{ __('head_officers.joined_date') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-9 col-7"><span>{{ $head_officer->created_at->format('d M, Y') }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection