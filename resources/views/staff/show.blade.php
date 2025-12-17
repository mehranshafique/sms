@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('staff.staff_details') }}</h4>
                    <p class="mb-0">{{ $staff->user->name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">{{ __('staff.staff_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $staff->user->name }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="profile-photo">
                                @if($staff->user->profile_picture)
                                    <img src="{{ asset('storage/'.$staff->user->profile_picture) }}" width="100" class="img-fluid rounded-circle">
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                                        <i class="fa fa-user fa-3x text-muted"></i>
                                    </div>
                                @endif
                            </div>
                            <h3 class="mt-4 mb-1">{{ $staff->user->name }}</h3>
                            <p class="text-muted">{{ $staff->designation ?? 'N/A' }}</p>
                            <span class="badge badge-primary">{{ $staff->user->roles->pluck('name')->join(', ') }}</span>
                            
                            <div class="d-flex justify-content-center mt-4">
                                <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-primary btn-sm me-2">
                                    <i class="fa fa-pencil me-1"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-8 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-personal-info">
                            <div class="row mb-2">
                                <div class="col-3"><h5 class="f-w-500">Email:</h5></div>
                                <div class="col-9"><span>{{ $staff->user->email }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-3"><h5 class="f-w-500">Phone:</h5></div>
                                <div class="col-9"><span>{{ $staff->user->phone ?? 'N/A' }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-3"><h5 class="f-w-500">Institution:</h5></div>
                                <div class="col-9"><span>{{ $staff->institution->name }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-3"><h5 class="f-w-500">Employee ID:</h5></div>
                                <div class="col-9"><span>{{ $staff->employee_id ?? 'N/A' }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-3"><h5 class="f-w-500">Joining Date:</h5></div>
                                <div class="col-9"><span>{{ $staff->joining_date ? $staff->joining_date->format('d M, Y') : 'N/A' }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-3"><h5 class="f-w-500">Status:</h5></div>
                                <div class="col-9">
                                    <span class="badge badge-{{ $staff->status == 'active' ? 'success' : 'warning' }}">{{ ucfirst($staff->status) }}</span>
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