@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('institute.institute_details') }}</h4>
                    <p class="mb-0">{{ $institute->name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('institutes.index') }}">{{ __('institute.institute_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $institute->name }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-photo">
                            <img src="{{ $institute->logo ? asset('storage/'.$institute->logo) : asset('images/no-image.png') }}" class="img-fluid rounded-circle" alt="" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <h3 class="mt-4 mb-1">{{ $institute->name }}</h3>
                        <p class="text-muted">{{ $institute->code }}</p>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <a href="{{ route('institutes.edit', $institute->id) }}" class="btn btn-primary pl-5 pr-5">{{ __('institute.edit') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-8 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('institute.basic_information') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-about-me">
                            <div class="pt-4 border-bottom-1 pb-4">
                                <h4 class="text-primary mb-4">{{ __('institute.contact_information') }}</h4>
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <h5 class="f-w-500">{{ __('institute.email') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-9"><span>{{ $institute->email ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <h5 class="f-w-500">{{ __('institute.phone') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-9"><span>{{ $institute->phone ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <h5 class="f-w-500">{{ __('institute.city') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-9"><span>{{ $institute->city ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <h5 class="f-w-500">{{ __('institute.full_address') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-9"><span>{{ $institute->address ?? 'N/A' }}</span></div>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-bottom-1 pb-4">
                                <h4 class="text-primary mb-4">{{ __('institute.details') }}</h4>
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <h5 class="f-w-500">{{ __('institute.type') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-9"><span>{{ ucfirst($institute->type) }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <h5 class="f-w-500">{{ __('institute.status') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-9">
                                        @if($institute->is_active)
                                            <span class="badge badge-success">{{ __('institute.active') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ __('institute.inactive') }}</span>
                                        @endif
                                    </div>
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