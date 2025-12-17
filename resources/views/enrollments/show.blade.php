@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('enrollment.enrollment_details') }}</h4>
                    <p class="mb-0">{{ $enrollment->student->full_name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">{{ __('enrollment.enrollment_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('enrollment.student_details') }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('enrollment.basic_information') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-personal-info">
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('enrollment.student_name') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $enrollment->student->full_name }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('enrollment.student_code') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $enrollment->student->admission_number }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('enrollment.class') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $enrollment->classSection->name ?? 'N/A' }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('enrollment.roll_no') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $enrollment->roll_number ?? 'N/A' }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('enrollment.status_label') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7">
                                    <span class="badge badge-{{ $enrollment->status == 'active' ? 'success' : 'warning' }}">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('enrollment.enrolled_at') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('d M, Y') : 'N/A' }}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-0 pt-0">
                        <a href="{{ route('enrollments.edit', $enrollment->id) }}" class="btn btn-primary d-block">{{ __('enrollment.edit_enrollment') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection