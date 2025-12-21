@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('subject.subject_management') }}</h4>
                    <p class="mb-0">{{ $subject->name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('subjects.index') }}">{{ __('subject.subject_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $subject->name }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('subject.basic_information') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-personal-info">
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('subject.subject_name') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $subject->name }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('subject.subject_code') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $subject->code ?? 'N/A' }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('subject.grade') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ $subject->gradeLevel->name ?? 'N/A' }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('subject.type') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7"><span>{{ ucfirst($subject->type) }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-5">
                                    <h5 class="f-w-500">{{ __('subject.status_label') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-8 col-7">
                                    @if($subject->is_active)
                                        <span class="badge badge-success">{{ __('subject.active') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('subject.inactive') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Academic Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-personal-info">
                            <div class="row mb-2">
                                <div class="col-sm-6 col-5">
                                    <h5 class="f-w-500">{{ __('subject.credit_hours') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-6 col-7"><span>{{ $subject->credit_hours ?? 0 }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6 col-5">
                                    <h5 class="f-w-500">{{ __('subject.total_marks') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-6 col-7"><span>{{ $subject->total_marks }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6 col-5">
                                    <h5 class="f-w-500">{{ __('subject.passing_marks') }} <span class="pull-right">:</span></h5>
                                </div>
                                <div class="col-sm-6 col-7"><span>{{ $subject->passing_marks }}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-0 pt-0">
                        <a href="{{ route('subjects.edit', $subject->id) }}" class="btn btn-primary d-block">{{ __('subject.edit_subject') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection