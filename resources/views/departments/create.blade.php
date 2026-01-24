@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('department.create_new') }}</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('department.basic_information') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('departments.store') }}" method="POST" id="departmentForm">
                            @csrf
                            <div class="row">
                                @if(!$institutionId && isset($institutions))
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('subject.institution_label') }} <span class="text-danger">*</span></label>
                                        <select name="institution_id" class="form-control default-select" required>
                                            <option value="">{{ __('subject.select_institution') }}</option>
                                            @foreach($institutions as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="institution_id" value="{{ $institutionId }}">
                                @endif

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('department.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="{{ __('department.enter_name') }}" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('department.code') }}</label>
                                    <input type="text" name="code" class="form-control" placeholder="{{ __('department.enter_code') }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('department.head_of_department') }}</label>
                                    <select name="head_of_department_id" class="form-control default-select" data-live-search="true">
                                        <option value="">{{ __('department.select_hod') }}</option>
                                        @foreach($staff as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3" id="submitBtn">{{ __('department.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        if(jQuery().selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

        // AJAX Submission
        $('#departmentForm').on('submit', function(e) {
            e.preventDefault();
            
            let btn = $('#submitBtn');
            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    btn.prop('disabled', false).html(originalText);
                    
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("department.success") }}',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        if(response.redirect) {
                            window.location.href = response.redirect;
                        }
                    });
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    let errors = xhr.responseJSON ? xhr.responseJSON.errors : null;
                    let errorMessage = '';
                    
                    if(errors) {
                        $.each(errors, function(key, value) {
                            errorMessage += value[0] + '<br>';
                        });
                    } else {
                        errorMessage = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __("department.something_went_wrong") }}';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("department.error_occurred") }}',
                        html: errorMessage
                    });
                }
            });
        });
    });
</script>
@endsection