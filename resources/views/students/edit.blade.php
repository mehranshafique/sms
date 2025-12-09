@extends('layout.layout')

@section('content')
    <style>
        .card { overflow: hidden; }
        .text-primary { color: #667eea !important; }
    </style>

    <div class="content-body">
        <div class="container-fluid">
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle p-2 me-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;">
                            <i class="fa fa-user-edit text-primary" style="font-size:24px;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ __('students.edit_student_title') }}</h3>
                            <small class="text-black">{{ __('students.edit_student_subtitle') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <form id="editForm">
                @csrf
                @method('PUT')

                <!-- Personal Details -->
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
                                        <input type="text" name="first_name" class="form-control" value="{{ $student->first_name }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.last_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control" value="{{ $student->last_name }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.gender') }} <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-select required single-select-placeholder">
                                            <option value="male" {{ $student->gender == 'male' ? 'selected' : '' }}>{{ __('students.male') }}</option>
                                            <option value="female" {{ $student->gender == 'female' ? 'selected' : '' }}>{{ __('students.female') }}</option>
                                            <option value="other" {{ $student->gender == 'other' ? 'selected' : '' }}>{{ __('students.other') }}</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.date_of_birth') }}</label>
                                        <input type="text" name="date_of_birth" id="mdate" class="form-control" value="{{ $student->date_of_birth }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.status') }} <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select required single-select-placeholder">
                                            <option value="active" {{ $student->status == 'active' ? 'selected' : '' }}>{{ __('students.active') }}</option>
                                            <option value="transferred" {{ $student->status == 'transferred' ? 'selected' : '' }}>{{ __('students.transferred') }}</option>
                                            <option value="withdrawn" {{ $student->status == 'withdrawn' ? 'selected' : '' }}>{{ __('students.withdrawn') }}</option>
                                            <option value="graduated" {{ $student->status == 'graduated' ? 'selected' : '' }}>{{ __('students.graduated') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optional Details -->
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
                                        <input type="text" name="national_id" class="form-control" value="{{ $student->national_id }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.nfc_tag_uid') }}</label>
                                        <input type="text" name="nfc_tag_uid" class="form-control" value="{{ $student->nfc_tag_uid }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('students.qr_code_token') }}</label>
                                        <input type="text" name="qr_code_token" class="form-control" value="{{ $student->qr_code_token }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Button -->
                <div class="d-flex justify-content-start gap-3 mt-3">
                    <button type="submit" class="btn btn-lg btn-primary">
                        <i class="bi bi-check-circle me-2"></i>{{ __('students.update_student_button') }}
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            $('#editForm').submit(function(e){
                e.preventDefault();
                $('.invalid-class').removeClass('invalid-class');

                let formData = new FormData(this);

                $.ajax({
                    url: "{{ route('students.update', $student->id) }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    success: function(response){
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("students.updated_title") }}',
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
                            $.each(errors, function(k, v){
                                messages += v.join('<br>') + '<br>';
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
