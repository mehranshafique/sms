@extends('layout.layout')

@section('content')
    <style>
        input{
            height: 48px !important;
        }
    </style>
    <style>
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

        .form-control,
        .form-select {
            border: 2px solid #e0e7ff;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .form-control:hover,
        .form-select:hover {
            border-color: #667eea;
        }

        .text-primary {
            color: #667eea !important;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(102, 126, 234, 0.3) !important;
        }

        .btn-light:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        textarea.form-control {
            resize: vertical;
        }
    </style>

    <div class="content-body">
        <div class="container-fluid">
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-white rounded-circle p-2 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">Edit Institute</h3>
                            <small class="text-black">Update institute details</small>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('institutes.update', $institute->id) }}" method="POST" id="editForm">
                @csrf
                @method('PUT')

                <!-- Basic Information Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="text-primary mb-3 pb-2 border-bottom">
                                        <i class="bi bi-info-circle me-2"></i>Basic Information
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-building me-1"></i>Institute Name
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="name" value="{{ $institute->name }}" class="form-control form-control-lg" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-hash me-1"></i>Institute Code
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="code" value="{{ $institute->code }}" class="form-control form-control-lg" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-diagram-3 me-1"></i>Institute Type
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select id="single-select" name="type" class="form-select form-select-lg" required>
                                                <option value="">-- Select Institute Type --</option>
                                                <option value="primary" {{ $institute->type == 'primary' ? 'selected' : '' }}>🎒 Primary School</option>
                                                <option value="secondary" {{ $institute->type == 'secondary' ? 'selected' : '' }}>📚 Secondary School</option>
                                                <option value="university" {{ $institute->type == 'university' ? 'selected' : '' }}>🎓 University</option>
                                                <option value="mixed" {{ $institute->type == 'mixed' ? 'selected' : '' }}>🏫 Mixed Level</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-toggle-on me-1"></i>Status
                                            </label>
                                            <select name="is_active" class="form-select form-select-lg single-select-placeholder js-states">
                                                <option value="1" {{ $institute->is_active ? 'selected' : '' }}>✅ Active</option>
                                                <option value="0" {{ !$institute->is_active ? 'selected' : '' }}>❌ Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Information Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="text-primary mb-3 pb-2 border-bottom">
                                        <i class="bi bi-geo-alt me-2"></i>Location Details
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-flag me-1"></i>Country
                                            </label>
                                            <input type="text" name="country" value="{{ $institute->country }}" class="form-control form-control-lg">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-pin-map me-1"></i>City
                                            </label>
                                            <input type="text" name="city" value="{{ $institute->city }}" class="form-control form-control-lg">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-house me-1"></i>Full Address
                                            </label>
                                            <textarea name="address" class="form-control form-control-lg" rows="2">{{ $institute->address }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="text-primary mb-3 pb-2 border-bottom">
                                        <i class="bi bi-telephone me-2"></i>Contact Information
                                    </h5>
                                    <div class="row g-3">

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-phone me-1"></i>Admin Email
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="email" value="{{ $institute->email }}" class="form-control form-control-lg" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-phone me-1"></i>Password (leave blank to keep current)
                                            </label>
                                            <input type="text" name="plan_password" class="form-control form-control-lg">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-phone me-1"></i>Phone Number
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="phone" value="{{ $institute->phone }}" class="form-control form-control-lg" required>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-3">
                    <button type="submit" class="btn btn-lg btn-primary shadow-sm">
                        <i class="bi bi-check-circle me-2"></i>Update Institute
                    </button>
                </div>
            </form>

        </div>
    </div>

    {{-- jQuery + SweetAlert --}}
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function(){

            $('#editForm').submit(function(e){
                e.preventDefault();

                // ------------------------------
                // FRONTEND JS VALIDATION
                // ------------------------------
                let requiredFields = ['name','code','type','email','phone'];
                let hasError = false;

                $.each(requiredFields, function(index, field){
                    let input = $(`[name="${field}"]`);
                    if(input.length && input.val().trim() === ''){
                        hasError = true;
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: `${field.replace('_',' ').toUpperCase()} is required`
                        });
                        return false; // break loop
                    }
                });

                if(hasError) return;

                // ------------------------------
                // AJAX SUBMIT
                // ------------------------------
                let formData = new FormData(this);

                $.ajax({
                    url: $(this).attr('action'),
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
                                title: 'Error Occurred',
                                text: 'Something went wrong!'
                            });
                        }
                    }
                });

            });

        });
    </script>

@endsection
