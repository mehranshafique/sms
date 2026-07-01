@extends('layout.layout')

@section('content')
@include('school_events.partials.styles')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('school_event.create') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('school_event.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('school-events.index') }}">{{ __('school_event.page_title') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('school_event.create') }}</li>
                </ol>
            </div>
        </div>

        @include('school_events._form')
    </div>
</div>
@endsection

@section('js')
<script>
$(function(){
    if (typeof window.digitexReinitSelectPickers === 'function') {
        window.digitexReinitSelectPickers();
    }
});
</script>
@endsection
