@extends('layout.layout')

@section('content')
    <style>
        input, select { height: 48px !important; }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .card { overflow: hidden; }
        .form-control, .form-select {
            border: 2px solid #e0e7ff;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .form-control:hover, .form-select:hover { border-color: #667eea; }
        .text-primary { color: #667eea !important; }
    </style>

    <div class="content-body">
        <div class="container-fluid">
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle p-2 me-3"
                             style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-user-edit text-primary" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">Edit Student</h3>
                            <small class="text-black">Update student information</small>
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
                                    <i class="bi bi-person me-2"></i>Personal Details
                                </h5>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control"
                                               value="{{ $student->first_name }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control"
                                               value="{{ $student->last_name }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-select required single-select-placeholder">
                                            <option value="male" {{ $student->gender == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ $student->gender == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ $student->gender == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Date of Birth</label>
                                        <input type="text" name="date_of_birth" id="mdate"
                                               class="form-control"
                                               value="{{ $student->date_of_birth }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select required single-select-placeholder">
                                            <option value="active" {{ $student->status == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="transferred" {{ $student->status == 'transferred' ? 'selected' : '' }}>Transferred</option>
                                            <option value="withdrawn" {{ $student->status == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                                            <option value="graduated" {{ $student->status == 'graduated' ? 'selected' : '' }}>Graduated</option>
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
                                    <i class="bi bi-card-list me-2"></i>Optional Details
                                </h5>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">National ID</label>
                                        <input type="text" name="national_id" class="form-control"
                                               value="{{ $student->national_id }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">NFC Tag UID</label>
                                        <input type="text" name="nfc_tag_uid" class="form-control"
                                               value="{{ $student->nfc_tag_uid }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">QR Code Token</label>
                                        <input type="text" name="qr_code_token" class="form-control"
                                               value="{{ $student->qr_code_token }}">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Button -->
                <div class="d-flex justify-content-start gap-3 mt-3">
                    <button type="submit" class="btn btn-lg btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Update Student
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        $(document).ready(function(){

            $('#editForm').submit(function(e){
                e.preventDefault();

                let hasError = false;

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
                            title: 'Updated!',
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
                                title: 'Validation Error',
                                html: messages
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong!'
                            });
                        }
                    }
                });

            });

        });
    </script>

@endsection
