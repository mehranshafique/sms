@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('transfer.page_title') }}</h4>
                    <p class="mb-0">{{ $student->full_name }} ({{ $student->admission_number }})</p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('transfer.issue_certificate') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('transfers.store', $student->id) }}" method="POST">
                            @csrf
                            
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                <strong>{{ __('transfer.warning') }}</strong> {{ __('transfer.warning_msg') }}
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('transfer.transfer_date') }}</label>
                                    <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('transfer.status_change') }}</label>
                                    <select name="status" class="form-control default-select">
                                        <option value="transferred">{{ __('transfer.status_transferred') }}</option>
                                        <option value="withdrawn">{{ __('transfer.status_withdrawn') }}</option>
                                        <option value="expelled">{{ __('transfer.status_expelled') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('transfer.reason') }}</label>
                                    <input type="text" name="reason" class="form-control" placeholder="{{ __('transfer.reason_placeholder') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('transfer.conduct') }}</label>
                                    <select name="conduct" class="form-control default-select">
                                        <option value="Excellent">{{ __('transfer.conduct_excellent') }}</option>
                                        <option value="Good">{{ __('transfer.conduct_good') }}</option>
                                        <option value="Satisfactory">{{ __('transfer.conduct_satisfactory') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('transfer.remarks') }}</label>
                                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <a href="{{ route('students.show', $student->id) }}" class="btn btn-secondary">{{ __('transfer.cancel') }}</a>
                                <button type="submit" class="btn btn-danger">{{ __('transfer.confirm') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection