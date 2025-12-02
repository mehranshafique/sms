@extends('layout.layout')

@section('content')
    <style>
        input, select {
            height: 48px !important;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .card {
            overflow: hidden;
        }
        .card-header {
            border: none;
        }
        .form-control, .form-select {
            border: 2px solid #e0e7ff;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .form-control:hover, .form-select:hover {
            border-color: #667eea;
        }
        .text-primary { color: #667eea !important; }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(102, 126, 234, 0.3) !important;
        }
        .btn-light:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        textarea.form-control { resize: vertical; }
    </style>


    <div class="content-body">
        <div class="container-fluid">
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle p-2 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-user text-primary" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">Add New Student</h3>
                            <small class="text-black">Fill in the details to register a new student</small>
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
                                    <i class="bi bi-person me-2"></i>Personal Details
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-select single-select-placeholder" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="date_of_birth" placeholder="2017-06-04" id="mdate">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select single-select-placeholder" required>
                                            <option value="active">Active</option>
                                            <option value="transferred">Transferred</option>
                                            <option value="withdrawn">Withdrawn</option>
                                            <option value="graduated">Graduated</option>
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
                                    <i class="bi bi-card-list me-2"></i>Optional Details
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">National ID</label>
                                        <input type="text" name="national_id" class="form-control" placeholder="National ID">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">NFC Tag UID</label>
                                        <input type="text" name="nfc_tag_uid" class="form-control" placeholder="NFC Tag UID">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">QR Code Token</label>
                                        <input type="text" name="qr_code_token" class="form-control" placeholder="QR Code Token">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-3 mt-3">
                    <button type="submit" class="btn btn-lg btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Save Student
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

                // Remove previous error class
                $('.invalid-class').removeClass('invalid-class');

                // Validate required fields
                $(this).find('.required').each(function(){
                    if($(this).val().trim() === ''){
                        $(this).addClass('invalid-class');
                        hasError = true;
                    }
                });

                if(hasError){
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fill all required fields'
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
                            title: 'Success!',
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
