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
                                <h5 class="modal-title">Add Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label>Role Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">Save</button>
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
                                <h5 class="modal-title">Edit Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label>Role Name</label>
                                    <input type="text" name="name" id="editRoleName" class="form-control" required>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">Update</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>


            <div class="row mb-3">
                <div class="col-12">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        Add Role
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
                                        <th>#</th>
                                        <th>Name</th>
                                        <th width="150">Action</th>
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

                                                    <button class="btn btn-xs sharp btn-danger" onclick="return confirm('Delete role?')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
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
        // Load role info into edit modal
        $(document).on('click', '.editRoleBtn', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');

            $('#editRoleId').val(id);
            $('#editRoleName').val(name);

            let action = "{{ url('roles') }}/" + id;
            $('#editRoleForm').attr('action', action);
        });
    </script>

@endsection

{{--@section('scripts')--}}

{{--@endsection--}}
