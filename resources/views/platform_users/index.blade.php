@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('platform_users.page_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('platform_users.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h4 class="card-title mb-0">{{ __('platform_users.user_list') }}</h4>
                <div class="d-flex flex-wrap gap-2">
                    <select id="filterInstitution" class="form-control form-control-sm default-select" style="min-width:180px;">
                        <option value="">{{ __('platform_users.all_institutions') }}</option>
                        @foreach($institutions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <select id="filterRole" class="form-control form-control-sm default-select" style="min-width:160px;">
                        <option value="">{{ __('platform_users.all_roles') }}</option>
                        @foreach($roles as $roleName => $label)
                            <option value="{{ $roleName }}">{{ $roleName }}</option>
                        @endforeach
                    </select>
                    <select id="filterStatus" class="form-control form-control-sm default-select" style="min-width:130px;">
                        <option value="">{{ __('platform_users.all_statuses') }}</option>
                        <option value="active">{{ __('platform_users.active') }}</option>
                        <option value="inactive">{{ __('platform_users.inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="display w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('platform_users.user') }}</th>
                                <th>{{ __('platform_users.username') }}</th>
                                <th>{{ __('platform_users.institution') }}</th>
                                <th>{{ __('platform_users.roles') }}</th>
                                <th>{{ __('platform_users.status') }}</th>
                                <th class="text-end">{{ __('platform_users.actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rolesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('platform_users.edit_roles') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong id="rolesUserName"></strong></p>
                <label class="form-label fw-bold">{{ __('platform_users.assign_roles') }}</label>
                <select id="rolesSelect" class="form-control default-select" multiple data-live-search="true">
                    @foreach($roles as $roleName => $label)
                        <option value="{{ $roleName }}">{{ $roleName }}</option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-2">{{ __('platform_users.roles_help') }}</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('platform_users.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="saveRolesBtn">{{ __('platform_users.save_roles') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    let currentUserId = null;
    const table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('platform.users.index') }}',
            data: function (d) {
                d.institution_id = $('#filterInstitution').val();
                d.role = $('#filterRole').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'id' },
            { data: 'details', name: 'name' },
            { data: 'username', name: 'username' },
            { data: 'institution', name: 'institute.name', orderable: false },
            { data: 'roles', name: 'roles', orderable: false, searchable: false },
            { data: 'is_active', name: 'is_active' },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' }
        ]
    });

    $('#filterInstitution, #filterRole, #filterStatus').on('change', () => table.ajax.reload());

    $(document).on('click', '.edit-roles-btn', function () {
        currentUserId = $(this).data('id');
        $('#rolesUserName').text($(this).data('name'));
        const roles = $(this).data('roles') || [];
        $('#rolesSelect').val(roles);
        if ($('#rolesSelect').hasClass('selectpicker')) {
            $('#rolesSelect').selectpicker('refresh');
        }
        $('#rolesModal').modal('show');
    });

    $('#saveRolesBtn').on('click', function () {
        if (!currentUserId) return;
        const roles = $('#rolesSelect').val() || [];
        if (!roles.length) {
            Swal.fire('{{ __('platform_users.error') }}', '{{ __('platform_users.select_at_least_one_role') }}', 'warning');
            return;
        }
        $.ajax({
            url: '{{ url('platform/users') }}/' + currentUserId + '/roles',
            method: 'PUT',
            data: { _token: '{{ csrf_token() }}', roles: roles },
            success: function (res) {
                $('#rolesModal').modal('hide');
                table.ajax.reload();
                Swal.fire('{{ __('platform_users.success') }}', res.message, 'success');
            },
            error: function (xhr) {
                Swal.fire('{{ __('platform_users.error') }}', xhr.responseJSON?.message || '{{ __('platform_users.error_occurred') }}', 'error');
            }
        });
    });
});
</script>
@endsection
