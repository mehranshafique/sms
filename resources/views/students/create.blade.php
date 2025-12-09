@extends('layout.layout')

@section('content')
    <style>
        .card { overflow: hidden; }
        .card-header { border: none; }
        .text-primary { color: #667eea !important; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(102, 126, 234, 0.3) !important; }
        .btn-light:hover { background-color: #f8f9fa; border-color: #dee2e6; }
        textarea.form-control { resize: vertical; }
    </style>

    <div class="content-body">
        <div class="container-fluid">
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle p-2 me-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;">
                            <i class="fa fa-user text-primary" style="font-size:24px;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ __('students.create_student_title') }}</h3>
                            <small class="text-black">{{ __('students.create_student_subtitle') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('students.store') }}" method="POST" id="createForm">
                @csrf

                <!-- Personal Details Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-primary mb-3 pb-2 border-bottom">
                                    <i class="bi bi-person me-2"></i>{{ __('students.personal_details') }}
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.first_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control required" placeholder="{{ __('students.first_name') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.last_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control required" placeholder="{{ __('students.last_name') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.gender') }} <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-select single-select-placeholder required" required>
                                            <option value="">{{ __('students.select_gender') }}</option>
                                            <option value="male">{{ __('students.male') }}</option>
                                            <option value="female">{{ __('students.female') }}</option>
                                            <option value="other">{{ __('students.other') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.date_of_birth') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control required" name="date_of_birth" placeholder="YYYY-MM-DD" id="mdate">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.status') }} <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select single-select-placeholder required" required>
                                            <option value="active">{{ __('students.active') }}</option>
                                            <option value="transferred">{{ __('students.transferred') }}</option>
                                            <option value="withdrawn">{{ __('students.withdrawn') }}</option>
                                            <option value="graduated">{{ __('students.graduated') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optional Details Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-primary mb-3 pb-2 border-bottom">
                                    <i class="bi bi-card-list me-2"></i>{{ __('students.optional_details') }}
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.national_id') }}</label>
                                        <input type="text" name="national_id" class="form-control" placeholder="{{ __('students.national_id') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.nfc_tag_uid') }}</label>
                                        <input type="text" name="nfc_tag_uid" class="form-control" placeholder="{{ __('students.nfc_tag_uid') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.qr_code_token') }}</label>
                                        <input type="text" name="qr_code_token" class="form-control" placeholder="{{ __('students.qr_code_token') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-3 mt-3">
                    <button type="submit" class="btn btn-lg btn-primary">
                        <i class="bi bi-check-circle me-2"></i>{{ __('students.save_student') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            $('#createForm').submit(function(e){
                e.preventDefault();

                let hasError = false;
                $('.invalid-class').removeClass('invalid-class');

                $(this).find('.required').each(function(){
                    if($(this).val().trim() === ''){
                        $(this).addClass('invalid-class');
                        hasError = true;
                    }
                });

                if(hasError){
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("students.validation_error_title") }}',
                        text: '{{ __("students.validation_error_text") }}'
                    });
                    return;
                }

                let formData = new FormData(this);

                $.ajax({
                    url: "{{ route('students.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    success: function(response){
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("students.success_title") }}',
                            text: response.message
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    },
                    error: function(xhr){
                        if(xhr.status === 422){
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value){
                                $(`[name="${key}"]`).addClass('invalid-class');
                            });
                            let messages = '';
                            $.each(errors, function(key, value){
                                messages += value.join('<br>') + '<br>';
                            });
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("students.validation_error_title") }}',
                                html: messages
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("students.error_title") }}',
                                text: '{{ __("students.something_went_wrong") }}'
                            });
                        }
                    }
                });

            });
        });
    </script>
@endsection
