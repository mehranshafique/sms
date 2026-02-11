@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Header Section --}}
        <div class="row page-titles mx-0 shadow-sm mb-4 bg-white rounded">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('institute.institute_details') }}</h4>
                    <p class="mb-0 text-muted">{{ $institute->name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('institutes.index') }}">{{ __('institute.institute_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $institute->acronym ?? $institute->name }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            
            {{-- Left Column: Identity & Quick Actions --}}
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow-sm overflow-hidden">
                    <div class="card-body">
                        <div class="profile-statistics text-center">
                            <div class="profile-photo mb-3">
                                <img src="{{ $institute->logo ? asset('storage/'.$institute->logo) : asset('images/no-image.png') }}" class="img-fluid rounded-circle shadow-sm border border-4 border-light" alt="" style="width: 140px; height: 140px; object-fit: cover;">
                            </div>
                            <h3 class="mt-4 mb-1 text-dark font-w600">{{ $institute->name }}</h3>
                            <p class="text-muted mb-2">{{ $institute->code }}</p>
                            
                            <div class="d-flex justify-content-center align-items-center mb-4">
                                @if($institute->is_active)
                                    <span class="badge badge-success badge-pill light px-3 py-2">
                                        <i class="fa fa-check-circle me-1"></i> {{ __('institute.active') }}
                                    </span>
                                @else
                                    <span class="badge badge-danger badge-pill light px-3 py-2">
                                        <i class="fa fa-times-circle me-1"></i> {{ __('institute.inactive') }}
                                    </span>
                                @endif
                                <span class="ms-2 badge badge-info badge-pill light px-3 py-2">
                                    {{ $institute->type instanceof \App\Enums\InstitutionType ? $institute->type->label() : ucfirst($institute->type) }}
                                </span>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <a href="{{ route('institutes.edit', $institute->id) }}" class="btn btn-primary w-100 shadow-sm">
                                        <i class="fa fa-pencil me-2"></i> {{ __('institute.edit') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 py-3">
                        <div class="row text-center">
                            <div class="col-4 border-end">
                                <h4 class="m-0 text-dark">{{ $institute->students->count() ?? 0 }}</h4>
                                <span class="fs-12 text-muted">{{ __('dashboard.students') }}</span>
                            </div>
                            <div class="col-4 border-end">
                                <h4 class="m-0 text-dark">{{ $institute->staff->count() ?? 0 }}</h4>
                                <span class="fs-12 text-muted">{{ __('dashboard.staff') }}</span>
                            </div>
                            <div class="col-4">
                                <h4 class="m-0 text-dark">{{ $institute->campuses->count() ?? 0 }}</h4>
                                <span class="fs-12 text-muted">{{ __('sidebar.campuses.title') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Right Column: Detailed Information --}}
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title text-primary"><i class="fa fa-info-circle me-2"></i> {{ __('institute.basic_information') }}</h4>
                    </div>
                    <div class="card-body pt-3">
                        
                        {{-- Contact Details Group --}}
                        <div class="mb-4">
                            <h5 class="text-uppercase fs-12 font-w600 text-muted mb-3 border-bottom pb-2">{{ __('institute.contact_information') }}</h5>
                            
                            <div class="row mb-3 align-items-center">
                                <div class="col-sm-4 col-5">
                                    <span class="text-dark font-w500"><i class="fa fa-envelope me-2 text-primary"></i> {{ __('institute.email') }}</span>
                                </div>
                                <div class="col-sm-8 col-7 text-muted">
                                    {{ $institute->email ?? 'N/A' }}
                                </div>
                            </div>
                            
                            <div class="row mb-3 align-items-center">
                                <div class="col-sm-4 col-5">
                                    <span class="text-dark font-w500"><i class="fa fa-phone me-2 text-primary"></i> {{ __('institute.phone') }}</span>
                                </div>
                                <div class="col-sm-8 col-7 text-muted">
                                    {{ $institute->phone ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        {{-- Location Group --}}
                        <div class="mb-4">
                            <h5 class="text-uppercase fs-12 font-w600 text-muted mb-3 border-bottom pb-2">{{ __('institute.location_details') }}</h5>
                            
                            <div class="row mb-3 align-items-center">
                                <div class="col-sm-4 col-5">
                                    <span class="text-dark font-w500"><i class="fa fa-map-marker me-2 text-primary"></i> {{ __('institute.city') }} / {{ __('institute.country') }}</span>
                                </div>
                                <div class="col-sm-8 col-7 text-muted">
                                    {{ $institute->cityRelation->name ?? $institute->city }} 
                                    @if($institute->stateRelation)
                                        , {{ $institute->stateRelation->name }}
                                    @endif
                                    @if($institute->countryRelation)
                                        , {{ $institute->countryRelation->name }}
                                    @endif
                                </div>
                            </div>

                            <div class="row mb-3 align-items-start">
                                <div class="col-sm-4 col-5">
                                    <span class="text-dark font-w500"><i class="fa fa-location-arrow me-2 text-primary"></i> {{ __('institute.full_address') }}</span>
                                </div>
                                <div class="col-sm-8 col-7 text-muted">
                                    {{ $institute->address ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        {{-- System Details Group --}}
                        <div>
                            <h5 class="text-uppercase fs-12 font-w600 text-muted mb-3 border-bottom pb-2">{{ __('institute.details') }}</h5>
                            
                            <div class="row mb-3 align-items-center">
                                <div class="col-sm-4 col-5">
                                    <span class="text-dark font-w500"><i class="fa fa-hashtag me-2 text-primary"></i> {{ __('institute.code') }}</span>
                                </div>
                                <div class="col-sm-8 col-7 text-muted fw-bold">
                                    {{ $institute->code }}
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-sm-4 col-5">
                                    <span class="text-dark font-w500"><i class="fa fa-calendar me-2 text-primary"></i> {{ __('dashboard.active_institutions') }}</span>
                                </div>
                                <div class="col-sm-8 col-7 text-muted">
                                    {{ $institute->created_at->format('d M, Y') }}
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