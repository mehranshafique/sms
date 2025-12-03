@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

            <div class="row page-titles mx-0 mb-3">
                <div class="col-sm-6 p-md-0 d-flex align-items-center">
                    <div class="bg-white rounded-circle p-2 me-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center;">
                        <i class="fa fa-user text-primary" style="font-size:24px;"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">Add Staff</h3>
                        <small class="text-black">Fill in the details to register a new staff member</small>
                    </div>
                </div>
            </div>

            <form action="{{ route('staff.store') }}" method="POST" id="createForm">
                @csrf

                <!-- User Details -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="text-primary mb-3 pb-2 border-bottom">
                                    <i class="bi bi-person me-2"></i>User Details
                                </h5>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Name</label>
                                        <input type="text" name="name" class="form-control mt-2" placeholder="Full Name">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" name="email" class="form-control mt-2" placeholder="Email">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Phone</label>
                                        <input type="text" name="phone" class="form-control mt-2" placeholder="Phone (optional)">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Password</label>
                                        <input type="password" name="password" class="form-control mt-2" placeholder="Password">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Address</label>
                                        <textarea class="form-control" placeholder="Address" name="address" id="address" cols="30" rows="4"></textarea>
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
                                    <i class="bi bi-card-list me-2"></i>Staff Details
                                </h5>
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Designation</label>
                                        <input type="text" name="designation" class="form-control" placeholder="Designation">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Department</label>
                                        <input type="text" name="department" class="form-control" placeholder="Department">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Hire Date</label>
                                        <input type="text" name="hire_date" class="form-control" placeholder="YYYY-MM-DD" id="mdate">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select single-select-placeholder" required>
                                            <option value="active">Active</option>
                                            <option value="on_leave">On Leave</option>
                                            <option value="terminated">Terminated</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-3 mt-3">
                    <button type="submit" class="btn btn-lg btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Save Staff
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
                        Swal.fire({icon:'success', title:'Success!', text:response.message})
                            .then(()=> window.location.href=response.redirect);
                    },
                    error: function(xhr){
                        if(xhr.status===422){
                            let errors = xhr.responseJSON.errors;
                            let messages = '';
                            $.each(errors, function(key,value){
                                messages += value.join('<br>') + '<br>';
                            });
                            Swal.fire({icon:'error', title:'Validation Error', html:messages});
                        } else {
                            Swal.fire({icon:'error', title:'Error', text:'Something went wrong!'});
                        }
                    }
                });
            });
        });
    </script>
@endsection
