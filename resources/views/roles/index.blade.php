@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
    
    <style>
        .dt-buttons .dropdown-toggle {
            background-color: #fff !important;
            color: #697a8d !important;
            border-color: #d9dee3 !important;
            box-shadow: 0 0.125rem 0.25rem 0 rgba(105, 122, 141, 0.1);
            border-radius: 0.375rem; 
            padding: 0.4375rem 1rem;
        }
        .dt-buttons {
            display: inline-flex;
            vertical-align: middle;
            gap: 10px;
            margin-bottom: 1rem;
        }
    </style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- TITLE BAR --}}
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('roles.role_management') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('roles.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @if(Auth::user()->can('role.create') || Auth::user()->hasRole('Super Admin'))
                <a href="{{ route('roles.create') }}" class="btn btn-primary btn-rounded shadow-sm fw-bold px-4 py-2">
                    <i class="fa fa-plus me-2"></i> {{ __('roles.create_new') }}
                </a>
                @endif
            </div>
        </div>

        {{-- STATS CARDS --}}
        <div class="row">
            <div class="col-xl-6 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary">
                                <i class="la la-shield"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('roles.total_roles') }}</p>
                                <h4 class="mb-0">{{ $totalRoles ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success">
                                <i class="la la-user-shield"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('roles.roles_with_users') }}</p>
                                <h4 class="mb-0">{{ $rolesWithUsers ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-white">
                        <h4 class="card-title mb-0 fw-bold fs-18">{{ __('roles.role_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table id="roleTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        <th>{{ __('roles.table_no') }}</th>
                                        <th>{{ __('roles.name') }}</th>
                                        <th>{{ __('roles.users_count') }}</th>
                                        <th class="text-end no-sort">{{ __('roles.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#roleTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('roles.index') }}",
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'users_count', name: 'users_count', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Delete Action
        $('#roleTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('roles.destroy', ':id') }}".replace(':id', id);
            
            Swal.fire({
                title: "{{ __('roles.are_you_sure') }}",
                text: "{{ __('roles.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('roles.yes_delete') }}",
                cancelButtonText: "{{ __('roles.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('roles.success') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire("{{ __('roles.error_occurred') }}", xhr.responseJSON.error || "{{ __('roles.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection