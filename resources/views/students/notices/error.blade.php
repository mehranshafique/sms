@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Error</h4>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-6">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="error-content">
                            <i class="fa fa-exclamation-triangle text-warning display-1 mb-4"></i>
                            <h3 class="card-title text-danger">Access Denied or Notice Not Found</h3>
                            <p class="card-text text-muted mb-4">
                                {{ session('error') ?? 'You do not have permission to view this notice, or it may have been removed.' }}
                            </p>
                            <a href="{{ route('student.notices.index') }}" class="btn btn-primary">
                                <i class="fa fa-arrow-left me-2"></i> Back to Notices
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection