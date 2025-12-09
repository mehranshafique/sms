@extends('layout.layout')

@section('content')

    <div class="content-body">
        <div class="container-fluid">

            <!-- Add Role Modal -->
            <div class="modal fade" id="addRoleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('roles.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('role.add_role') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label>{{ __('role.role_name') }}</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">{{ __('role.save') }}</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Role Modal -->
            <div class="modal fade" id="editRoleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editRoleForm" method="POST">
                            @csrf
                            @method('PUT')

                            <input type="hidden" id="editRoleId">

                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('role.edit_role') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label>{{ __('role.role_name') }}</label>
                                    <input type="text" name="name" id="editRoleName" class="form-control" required>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">{{ __('role.update') }}</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        {{ __('role.add_role_button') }}
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example" class="display" style="min-width: 845px">
                                    <thead>
                                    <tr>
                                        <th>{{ __('role.table_id') }}</th>
                                        <th>{{ __('role.table_name') }}</th>
                                        <th>{{ __('role.table_action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($roles as $key => $role)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td>{{ $role->name }}</td>

                                            <td>
                                                <a href="{{ route('roles.assign-permissions', $role->id) }}" class="btn btn-xs sharp btn-info">
                                                    <i class="fa fa-key"></i>
                                                </a>
                                                @if($role->can_delete == 0)
                                                    <button
                                                        class="btn btn-xs sharp btn-primary editRoleBtn"
                                                        data-id="{{ $role->id }}"
                                                        data-name="{{ $role->name }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editRoleModal"
                                                    >
                                                        <i class="fa fa-pencil"></i>
                                                    </button>

                                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button class="btn btn-xs sharp btn-danger" onclick="return confirm('{{ __('role.delete_confirmation') }}')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
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
        </div>
    </div>

    <script>
        // ---------------------------
        // CREATE ROLE (AJAX)
        // ---------------------------
        $('#addRoleModal form').submit(function (e) {
            e.preventDefault();

            let form = $(this);
            let url = form.attr('action');

            $.ajax({
                url: url,
                type: "POST",
                data: form.serialize(),
                success: function (res) {
                    Swal.fire({
                        icon: "success",
                        title: "{{ __('role.success') }}",
                        text: res.message,
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let msg = Object.values(errors).join("<br>");

                        Swal.fire({
                            icon: "error",
                            title: "{{ __('role.validation_error') }}",
                            html: msg
                        });
                    }
                }
            });
        });


        // ---------------------------
        // UPDATE ROLE (AJAX)
        // ---------------------------
        $('#editRoleForm').submit(function (e) {
            e.preventDefault();

            let id = $('#editRoleId').val();
            let url = "/roles/" + id;

            $.ajax({
                url: url,
                type: "POST",
                data: $('#editRoleForm').serialize(),
                success: function (res) {
                    Swal.fire({
                        icon: "success",
                        title: "{{ __('role.updated') }}",
                        text: res.message,
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let msg = Object.values(errors).join("<br>");

                        Swal.fire({
                            icon: "error",
                            title: "{{ __('role.validation_error') }}",
                            html: msg
                        });
                    }
                }
            });
        });


        // ---------------------------
        // LOAD ROLE DATA IN EDIT MODAL
        // ---------------------------
        $(document).on('click', '.editRoleBtn', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');

            $('#editRoleId').val(id);
            $('#editRoleName').val(name);
        });
    </script>

@endsection
