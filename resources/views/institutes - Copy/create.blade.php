@extends('layout.layout')

@section('content')

    <style>
        .card {
            overflow: hidden;
        }

        .card-header {
            border: none;
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
                            <h3 class="mb-0 fw-bold">{{ __('institute.add_new_institute') }}</h3>
                            <small class="text-black">{{ __('institute.add_new_institute_subtitle') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('institutes.store') }}" method="POST" id="createForm">
                @csrf
                <!-- Basic Information Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5 class="text-primary mb-3 pb-2 border-bottom">
                                        <i class="bi bi-info-circle me-2"></i>{{ __('institute.basic_information') }}
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-building me-1"></i>{{ __('institute.institute_name') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="name" class="form-control" placeholder="{{ __('institute.enter_institute_name') }}" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-diagram-3 me-1"></i>{{ __('institute.institute_type') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select id="single-select" name="type" class="form-select form-select-lg" required>
                                                <option value="">{{ __('institute.select_institute_type') }}</option>
                                                <option value="primary">🎒 {{ __('institute.primary_school') }}</option>
                                                <option value="secondary">📚 {{ __('institute.secondary_school') }}</option>
                                                <option value="university">🎓 {{ __('institute.university') }}</option>
                                                <option value="mixed">🏫 {{ __('institute.mixed_level') }}</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-toggle-on me-1"></i>{{ __('institute.status') }}
                                            </label>
                                            <select name="is_active" class="form-select form-select-lg single-select-placeholder js-states">
                                                <option value="1" selected>✅ {{ __('institute.active') }}</option>
                                                <option value="0">❌ {{ __('institute.inactive') }}</option>
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
                                        <i class="bi bi-geo-alt me-2"></i>{{ __('institute.location_details') }}
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-flag me-1"></i>{{ __('institute.country') }}
                                            </label>
                                            <input type="text" name="country" class="form-control" placeholder="{{ __('institute.enter_country') }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-pin-map me-1"></i>{{ __('institute.city') }}
                                            </label>
                                            <input type="text" name="city" class="form-control" placeholder="{{ __('institute.enter_city') }}">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-house me-1"></i>{{ __('institute.full_address') }}
                                            </label>
                                            <textarea name="address" class="form-control" rows="2" placeholder="{{ __('institute.enter_full_address') }}"></textarea>
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
                                        <i class="bi bi-telephone me-2"></i>{{ __('institute.contact_information') }}
                                    </h5>
                                    <div class="row g-3">

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-phone me-1"></i>{{ __('institute.admin_email') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="email" class="form-control" placeholder="{{ __('institute.enter_admin_email') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-phone me-1"></i>{{ __('institute.password') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="plan_password" class="form-control" placeholder="{{ __('institute.enter_password') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-phone me-1"></i>{{ __('institute.phone_number') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="phone" class="form-control" placeholder="{{ __('institute.enter_phone_number') }}" required>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-3">
                    <button type="submit" class="btn btn-lg btn-primary shadow-sm">
                        <i class="bi bi-check-circle me-2"></i>{{ __('institute.save_institute') }}
                    </button>
                </div>
            </form>

        </div>
    </div>

    {{-- jQuery + SweetAlert --}}
    {{-- <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}

    <script>
        $(document).ready(function(){

            // map form field names to user-friendly translated labels
            const fieldLabels = {
                name: '{{ __("institute.institute_name") }}',
                code: '{{ __("institute.code") }}',
                type: '{{ __("institute.institute_type") }}',
                email: '{{ __("institute.admin_email") }}',
                plan_password: '{{ __("institute.password") }}',
                phone: '{{ __("institute.phone_number") }}',
            };

            $('#createForm').submit(function(e){
                e.preventDefault();

                // ------------------------------
                // FRONTEND JS VALIDATION
                // ------------------------------
                let requiredFields = ['name','code','type','email','plan_password','phone'];
                let hasError = false;

                $.each(requiredFields, function(index, field){
                    let input = $(`[name="${field}"]`);
                    if(input.length && input.val().trim() === ''){
                        hasError = true;
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("institute.validation_error") }}',
                            text: (fieldLabels[field] ?? field).toString() + ' {{ __("institute.is_required") }}'
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
                    url: "{{ route('institutes.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    success: function(response){
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("institute.success") }}',
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
                                title: '{{ __("institute.validation_error") }}',
                                html: messages
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("institute.error_occurred") }}',
                                text: '{{ __("institute.something_went_wrong") }}'
                            });
                        }
                    }
                });

            });

        });
    </script>

@endsection
