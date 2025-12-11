@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

            <div class="row page-titles mx-0 mb-3">
                <div class="col-sm-6 p-md-0 d-flex align-items-center">
                    <div class="bg-white rounded-circle p-2 me-3"
                         style="width:50px; height:50px; display:flex; align-items:center; justify-content:center;">
                        <i class="fa fa-user text-primary" style="font-size:24px;"></i>
                    </div>

                    <div>
                        <h3 class="mb-0 fw-bold">{{ __('staff.create.title') }}</h3>
                        <small class="text-black">{{ __('staff.create.subtitle') }}</small>
                    </div>
                </div>
            </div>

            <form method="POST" id="createForm">
                @csrf

                <!-- User Details -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="text-primary mb-3 pb-2 border-bottom">
                                    <i class="bi bi-person me-2"></i>{{ __('staff.create.sections.user_details') }}
                                </h5>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.name') }}</label>
                                        <input type="text" name="name" class="form-control"
                                               placeholder="{{ __('staff.create.placeholders.name') }}">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.email') }}</label>
                                        <input type="email" name="email" class="form-control"
                                               placeholder="{{ __('staff.create.placeholders.email') }}">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.phone') }}</label>
                                        <input type="text" name="phone" class="form-control"
                                               placeholder="{{ __('staff.create.placeholders.phone') }}">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.password') }}</label>
                                        <input type="password" name="password" class="form-control"
                                               placeholder="{{ __('staff.create.placeholders.password') }}">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.role') }}</label>
                                        <select name="role" class="form-control single-select-placeholder">
                                            <option value="Finance">Finance</option>
                                            <option value="Teacher">Teacher</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.address') }}</label>
                                        <textarea class="form-control"
                                                  placeholder="{{ __('staff.create.placeholders.address') }}"
                                                  name="address" cols="30" rows="4"></textarea>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Details -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="text-primary mb-3 pb-2 border-bottom">
                                    <i class="bi bi-card-list me-2"></i>{{ __('staff.create.sections.staff_details') }}
                                </h5>

                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.designation') }}</label>
                                        <input type="text" name="designation" class="form-control"
                                               placeholder="{{ __('staff.create.placeholders.designation') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.department') }}</label>
                                        <input type="text" name="department" class="form-control"
                                               placeholder="{{ __('staff.create.placeholders.department') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.hire_date') }}</label>
                                        <input type="text" name="hire_date" class="form-control"
                                               placeholder="{{ __('staff.create.placeholders.hire_date') }}"
                                               id="mdate">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('staff.create.fields.status') }} <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select single-select-placeholder" required>
                                            <option value="active">{{ __('staff.create.status_options.active') }}</option>
                                            <option value="on_leave">{{ __('staff.create.status_options.on_leave') }}</option>
                                            <option value="terminated">{{ __('staff.create.status_options.terminated') }}</option>
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-3 mt-3">
                    <button type="submit" class="btn btn-lg btn-primary">
                        <i class="bi bi-check-circle me-2"></i>{{ __('staff.create.buttons.save') }}
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        $(document).ready(function(){
            $('#createForm').submit(function(e){
                e.preventDefault();

                let formData = new FormData(this);

                $.ajax({
                    url: "{{ route('staff.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},

                    success: function(response){
                        Swal.fire({
                            icon:'success',
                            title:'Success!',
                            text:response.message
                        }).then(()=> window.location.href=response.redirect);
                    },

                    error: function(xhr){
                        if(xhr.status===422){
                            let errors = xhr.responseJSON.errors;
                            let messages = '';
                            $.each(errors, function(key,value){
                                messages += value.join('<br>') + '<br>';
                            });

                            Swal.fire({
                                icon:'error',
                                title:'Validation Error',
                                html:messages
                            });

                        } else {
                            Swal.fire({icon:'error', title:'Error', text:'Something went wrong!'});
                        }
                    }
                });
            });
        });
    </script>
@endsection
