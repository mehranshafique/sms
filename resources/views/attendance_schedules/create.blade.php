@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('attendance_schedule.create_title') }}</h4>
                    <p class="mb-0">{{ __('attendance_schedule.page_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('attendance-schedules.index') }}">{{ __('attendance_schedule.list_title') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('attendance_schedule.create_title') }}</a></li>
                </ol>
            </div>
        </div>

        @include('attendance_schedules._form')
    </div>
</div>
@endsection

@section('js')
@include('attendance_schedules._form_js')
@endsection
