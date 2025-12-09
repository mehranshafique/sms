@extends('layout.layout')

@section('content')

    <div class="content-body">
        <div class="container-fluid">

            <!-- Add Permission Modal -->
            <div class="modal fade" id="addPermissionModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('permissions.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('modules.add_permission') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label>{{ __('modules.permission_name') }}</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <input type="hidden" name="module_id" value="{{ $module->id  }}">
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">{{ __('modules.add_permission_button') }}</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Permission Modal -->
            <div class="modal fade" id="editPermissionModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editPermissionForm" method="POST">
                            @csrf
                            @method('PUT')

                            <input type="hidden" id="editPermissionId">

                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('modules.edit_permission') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" value="{{ $module->id  }}" name="module_id">
                                <div class="form-group mb-3">
                                    <label>{{ __('modules.permission_name') }}</label>
                                    <input type="text" name="name" id="editPermissionName" class="form-control" required>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">{{ __('modules.update_permission_button') }}</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="row page-titles mx-0 py-3">
                <div class="col-sm-12 p-md-0 ">
                    <div class="d-flex align-items-center justify-content-between">
                        <h4>{{ $module->name }}</h4>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
                            {{ __('modules.add_permission_button') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Permissions Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example" class="display" style="min-width: 845px">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('modules.permission_name') }}</th>
                                        <th>{{ __('modules.module_name') }}</th>
                                        <th>{{ __('modules.actions') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($permissions as $key => $permission)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td>{{ $permission->name }}</td>
                                            <td>{{ $permission->module->name }}</td>
                                            <td>
                                                <button
                                                    class="btn btn-xs sharp btn-primary editPermissionBtn"
                                                    data-id="{{ $permission->id }}"
                                                    data-name="{{ explode('.',$permission->name)[1] }}"
                                                    data-module="{{ $permission->module_id }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editPermissionModal"
                                                >
                                                    <i class="fa fa-pencil"></i> {{ __('modules.edit_button') }}
                                                </button>

                                                <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-xs sharp btn-danger delete-btn" type="button">
                                                        <i class="fa fa-trash"></i> {{ __('modules.delete_button') }}
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Load permission info into edit modal
        $(document).on('click', '.editPermissionBtn', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let moduleId = $(this).data('module');

            $('#editPermissionId').val(id);
            $('#editPermissionName').val(name);
            $('#editModuleSelect').val(moduleId);

            let action = "{{ url('permissions') }}/" + id;
            $('#editPermissionForm').attr('action', action);
        });

        // Delete confirmation with SweetAlert
        $(document).on('click', '.delete-btn', function () {
            let form = $(this).closest('form');
            Swal.fire({
                title: '{{ __("modules.delete_confirmation_title") }}',
                text: '{{ __("modules.delete_permission_text") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("modules.delete_confirm_button") }}',
                cancelButtonText: '{{ __("modules.delete_cancel_button") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>

@endsection
