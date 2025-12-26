@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid text-center">
        <div class="row justify-content-center h-100 align-items-center">
            <div class="col-md-6">
                <div class="error-page">
                    <div class="error-inner text-center">
                        <h1 class="error-text text-danger"><i class="fa fa-exclamation-circle"></i></h1>
                        <h4 class="mt-4"><i class="fa fa-thumbs-down text-danger"></i> {{ __('voting.access_denied') }}</h4>
                        <p>{{ $message }}</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">{{ __('voting.back_to_dashboard') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection