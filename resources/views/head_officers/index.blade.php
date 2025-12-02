@extends('layout.layout')

@section('content')
    <div class="content-body">

        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <!-- Add Module Button -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addHeaderOfficersModal">Add Header Officer</button>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example" class="display" style="min-width: 845px">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Password</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Total Assigned Institution</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
{{--                                    <tbody>--}}
{{--                                    @foreach($modules as $module)--}}
{{--                                        <tr>--}}
{{--                                            <td>{{ $module->name }}</td>--}}
{{--                                            <td>{{ $module->slug }}</td>--}}
{{--                                           --}}
{{--                                            <td>--}}
{{--                                                <a href="{{ route('permissions.index',$module->id)  }}" class="btn btn-xs sharp btn-info">--}}
{{--                                                    <i class="fa fa-key"></i>--}}
{{--                                                </a>--}}

{{--                                                <button--}}
{{--                                                    class="btn btn-xs sharp btn-primary edit-btn"--}}
{{--                                                    data-id="{{ $module->id }}"--}}
{{--                                                    data-name="{{ $module->name }}"--}}
{{--                                                    --}}{{----}}{{--                                                        data-bs-toggle="modal"--}}
{{--                                                    --}}{{----}}{{--                                                        data-bs-target="#editModuleModal"--}}
{{--                                                >--}}
{{--                                                    <i class="fa fa-pencil"></i>--}}
{{--                                                </button>--}}


{{--                                                <form action="{{ route('modules.destroy', $module->id) }}" method="POST" class="d-inline">--}}
{{--                                                    @csrf--}}
{{--                                                    @method('DELETE')--}}

{{--                                                    <button class="btn btn-xs sharp btn-danger delete-btn" >--}}
{{--                                                        <i class="fa fa-trash"></i>--}}
{{--                                                    </button>--}}
{{--                                                </form>--}}
{{--                                            </td>--}}
{{--                                        </tr>--}}
{{--                                    @endforeach--}}
{{--                                    </tbody>--}}

                                    <tbody>
                                    @foreach($head_officers as $officer)
                                        <tr id="row-{{ $officer->id }}">
                                            <td>{{ $officer->name }}</td>
                                            <td>{{ $officer->email }}</td>
                                            <td>******</td>
                                            <td>{{ $officer->phone }}</td>
                                            <td>{{ $officer->address }}</td>
                                            <td>0</td>

                                            <td>
                                                <button class="btn btn-xs sharp btn-primary edit-btn"
                                                        data-id="{{ $officer->id }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editHeaderOfficerModal">
                                                    <i class="fa fa-pencil"></i>
                                                </button>

                                                <button class="btn btn-xs sharp btn-danger delete-btn" data-id="{{ $officer->id }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>


                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Module Modal -->
            <div class="modal fade" id="addHeaderOfficersModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form id="addForm" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Head Officers</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6">
                                    <label for="name">Name</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter Name" >
                                </div>
                                <div class="col-6">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter Email" >
                                </div>
                                <div class="col-6 my-3">
                                    <label for="password">Password</label>
                                    <input type="text" name="password" id="password" class="form-control" placeholder="Enter Password" >
                                </div>
                                <div class="col-6 my-3">
                                    <label for="phone">Phone</label>
                                    <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter Phone" >
                                </div>
                                <div class="col-12">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" rows="4" cols="10" name="address" id="address" placeholder="Enter Address"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Add Module</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Module Modal -->
            <div class="modal fade" id="editHeaderOfficerModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form id="editForm" class="modal-content">
                        @csrf
                        @method('PUT')

                        <input type="hidden" id="edit_id" name="id">

                        <div class="modal-header">
                            <h5 class="modal-title">Edit Head Officer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6">
                                    <label>Name</label>
                                    <input type="text" id="edit_name" name="name" class="form-control">
                                </div>

                                <div class="col-6">
                                    <label>Email</label>
                                    <input type="email" id="edit_email" name="email" class="form-control">
                                </div>

                                <div class="col-6 my-3">
                                    <label>Password (optional)</label>
                                    <input type="text" id="edit_password" name="password" class="form-control">
                                </div>

                                <div class="col-6 my-3">
                                    <label>Phone</label>
                                    <input type="text" id="edit_phone" name="phone" class="form-control">
                                </div>

                                <div class="col-12">
                                    <label>Address</label>
                                    <textarea id="edit_address" name="address" class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-primary">Update</button>
                        </div>

                    </form>
                </div>
            </div>


        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>

        $('#addForm').submit(function(e){
            e.preventDefault();
            let name = $('#name').val();
            let email = $('#email').val();
            let phone = $('#phone').val();
            let password = $('#password').val();
            let address = $('#address').val();
            console.log(name, email, phone, password, address);

            if(name == ''){
                Swal.fire('Validation','Name field is required.','error');
                return;
            }
            if(phone == ''){
                Swal.fire('Validation','Phone field is required.','error');
                return;
            }
            if(email == ''){
                Swal.fire('Validation','Email field is required.','error');
                return;
            }
            if(password == ''){
                Swal.fire('Validation','Password field is required.','error');
                return;
            }
            if(address == ''){
                Swal.fire('Validation','Address field is required.','error');
                return;
            }

            $.ajax({
                url:"{{ route('header-officers.store')  }}",
                method:"POST",
                data:{
                    name,
                    email,
                    phone,
                    password,
                    address,
                    _token:"{{ csrf_token()  }}"
                },
                success:function(res){
                    Swal.fire('Success', res.message, 'success').then(() => {
                        location.reload();
                    });
                    // window.href.reload();
                },
                error: function(xhr){
                    let errors = xhr.responseJSON.errors;
                    let msg = Object.values(errors)[0][0];
                    Swal.fire('Error', msg, 'error');
                }

            })

        })

        $('.edit-btn').click(function () {
            let id = $(this).data('id');

            $.get('/header-officers/' + id + '/edit', function(res) {
                $('#edit_id').val(res.id);
                $('#edit_name').val(res.name);
                $('#edit_email').val(res.email);
                $('#edit_phone').val(res.phone);
                $('#edit_address').val(res.address);
            });
        });

        $('#editForm').submit(function(e){
            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({
                url: "/header-officers/" + id,
                method: "POST",
                data: $(this).serialize(),
                success: function(res){
                    Swal.fire('Updated!', res.message, 'success').then(() => {
                        location.reload();
                    });
                },
                error: function(xhr){
                    let errors = xhr.responseJSON.errors;
                    let msg = Object.values(errors)[0][0];
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        $('.delete-btn').click(function () {

            let id = $(this).data('id');

            Swal.fire({
                title: "Are you sure?",
                text: "Officer will be deleted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Delete"
            }).then(result => {

                if(result.isConfirmed){
                    $.ajax({
                        url: "/header-officers/" + id,
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: function(res){
                            Swal.fire('Deleted!', res.message, 'success');
                            $('#row-' + id).remove();
                        }
                    });
                }
            });
        });


    </script>

@endsection

{{--@section('scripts')--}}
<!-- jQuery CDN (latest version 3.x) -->



{{--@endsection--}}
