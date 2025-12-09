@extends('layout.layout')

@section('content')
    <div class="content-body">

        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <!-- Add Head Officer Button -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addHeaderOfficersModal">
                {{ __('head_officers.add_header_officer') }}
            </button>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="table-responsive">
                                <table id="example" class="display" style="min-width: 845px">
                                    <thead>
                                    <tr>
                                        <th>{{ __('head_officers.name') }}</th>
                                        <th>{{ __('head_officers.email') }}</th>
                                        <th>{{ __('head_officers.password') }}</th>
                                        <th>{{ __('head_officers.phone') }}</th>
                                        <th>{{ __('head_officers.address') }}</th>
                                        <th>{{ __('head_officers.total_institution') }}</th>
                                        <th>{{ __('head_officers.actions') }}</th>
                                    </tr>
                                    </thead>

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

            <!-- Add Head Officer Modal -->
            <div class="modal fade" id="addHeaderOfficersModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form id="addForm" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('head_officers.add_head_officers') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6">
                                    <label>{{ __('head_officers.name') }}</label>
                                    <input type="text" name="name" id="name" class="form-control"
                                           placeholder="{{ __('head_officers.enter_name') }}">
                                </div>
                                <div class="col-6">
                                    <label>{{ __('head_officers.email') }}</label>
                                    <input type="email" name="email" id="email" class="form-control"
                                           placeholder="{{ __('head_officers.enter_email') }}">
                                </div>

                                <div class="col-6 my-3">
                                    <label>{{ __('head_officers.password') }}</label>
                                    <input type="text" name="password" id="password" class="form-control"
                                           placeholder="{{ __('head_officers.enter_password') }}">
                                </div>

                                <div class="col-6 my-3">
                                    <label>{{ __('head_officers.phone') }}</label>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                           placeholder="{{ __('head_officers.enter_phone') }}">
                                </div>

                                <div class="col-12">
                                    <label>{{ __('head_officers.address') }}</label>
                                    <textarea class="form-control" rows="4" name="address" id="address"
                                              placeholder="{{ __('head_officers.enter_address') }}"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">{{ __('head_officers.add') }}</button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Edit Head Officer Modal -->
            <div class="modal fade" id="editHeaderOfficerModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form id="editForm" class="modal-content">
                        @csrf
                        @method('PUT')

                        <input type="hidden" id="edit_id" name="id">

                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('head_officers.edit_head_officer') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">

                                <div class="col-6">
                                    <label>{{ __('head_officers.name') }}</label>
                                    <input type="text" id="edit_name" name="name" class="form-control">
                                </div>

                                <div class="col-6">
                                    <label>{{ __('head_officers.email') }}</label>
                                    <input type="email" id="edit_email" name="email" class="form-control">
                                </div>

                                <div class="col-6 my-3">
                                    <label>{{ __('head_officers.password_optional') }}</label>
                                    <input type="text" id="edit_password" name="password" class="form-control">
                                </div>

                                <div class="col-6 my-3">
                                    <label>{{ __('head_officers.phone') }}</label>
                                    <input type="text" id="edit_phone" name="phone" class="form-control">
                                </div>

                                <div class="col-12">
                                    <label>{{ __('head_officers.address') }}</label>
                                    <textarea id="edit_address" name="address" class="form-control" rows="4"></textarea>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-primary">{{ __('head_officers.update') }}</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Add Validation
        $('#addForm').submit(function(e){
            e.preventDefault();

            let name = $('#name').val();
            let email = $('#email').val();
            let phone = $('#phone').val();
            let password = $('#password').val();
            let address = $('#address').val();

            if(name === ''){
                Swal.fire('{{ __("head_officers.validation") }}','{{ __("head_officers.name_required") }}','error');
                return;
            }
            if(phone === ''){
                Swal.fire('{{ __("head_officers.validation") }}','{{ __("head_officers.phone_required") }}','error');
                return;
            }
            if(email === ''){
                Swal.fire('{{ __("head_officers.validation") }}','{{ __("head_officers.email_required") }}','error');
                return;
            }
            if(password === ''){
                Swal.fire('{{ __("head_officers.validation") }}','{{ __("head_officers.password_required") }}','error');
                return;
            }
            if(address === ''){
                Swal.fire('{{ __("head_officers.validation") }}','{{ __("head_officers.address_required") }}','error');
                return;
            }

            $.ajax({
                url:"{{ route('header-officers.store') }}",
                method:"POST",
                data:{name, email, phone, password, address, _token:"{{ csrf_token() }}"},
                success:function(res){
                    Swal.fire('{{ __("head_officers.success") }}', res.message, 'success').then(() => {
                        location.reload();
                    });
                },
                error:function(xhr){
                    let msg = Object.values(xhr.responseJSON.errors)[0][0];
                    Swal.fire('{{ __("head_officers.error") }}', msg, 'error');
                }
            });

        });

        // Edit Load
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

        // Edit Submit
        $('#editForm').submit(function(e){
            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({
                url: "/header-officers/" + id,
                method: "POST",
                data: $(this).serialize(),
                success: function(res){
                    Swal.fire('{{ __("head_officers.updated") }}', res.message, 'success')
                        .then(() => location.reload());
                },
                error: function(xhr){
                    let msg = Object.values(xhr.responseJSON.errors)[0][0];
                    Swal.fire('{{ __("head_officers.error") }}', msg, 'error');
                }
            });

        });

        // Delete
        $('.delete-btn').click(function () {
            let id = $(this).data('id');

            Swal.fire({
                title: "{{ __('head_officers.are_you_sure') }}",
                text: "{{ __('head_officers.officer_deleted_warning') }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "{{ __('head_officers.delete') }}"
            }).then(result => {

                if(result.isConfirmed){
                    $.ajax({
                        url: "/header-officers/" + id,
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: function(res){
                            Swal.fire('{{ __("head_officers.deleted") }}', res.message, 'success');
                            $('#row-' + id).remove();
                        }
                    });
                }
            });
        });
    </script>

@endsection
