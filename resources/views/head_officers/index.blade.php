@extends('layout.layout')

@section('styles')
    <style>
        .dt-button-collection {
            z-index: 2001 !important;
        }
        
        /* Head Officer Icon in Table Details */
        .head-officer-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px; 
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            margin-right: 15px;
        }
    </style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- 1. TITLE BAR & BREADCRUMB --}}
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('head_officers.officer_management') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('head_officers.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                
                <button class="btn btn-primary btn-rounded shadow-sm fw-bold px-4 py-2" data-bs-toggle="modal" data-bs-target="#addHeaderOfficersModal">
                    <i class="fa fa-plus me-2"></i> {{ __('head_officers.create_new') }}
                </button>
                
            </div>
        </div>

        {{-- 2. STATS CARDS (Single Row) --}}
        <div class="row mb-4">
            {{-- Total Officers --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card h-100">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary">
                                <i class="la la-users"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1 text-muted text-uppercase fs-13 font-w600">{{ __('head_officers.total_officers') }}</p>
                                <h4 class="mb-0 fs-20 font-w700">{{ $totalOfficers ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Officers --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card h-100">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success">
                                <i class="la la-user-check"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1 text-muted text-uppercase fs-13 font-w600">{{ __('head_officers.active_officers') }}</p>
                                <h4 class="mb-0 fs-20 font-w700">{{ $activeOfficers ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Inactive Officers --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card h-100">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-danger text-danger">
                                <i class="la la-user-times"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1 text-muted text-uppercase fs-13 font-w600">{{ __('head_officers.inactive') }}</p>
                                <h4 class="mb-0 fs-20 font-w700">{{ $inactiveOfficers ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- New This Month --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card h-100">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info">
                                <i class="la la-calendar-plus-o"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1 text-muted text-uppercase fs-13 font-w600">{{ __('head_officers.new_this_month') }}</p>
                                <h4 class="mb-0 fs-20 font-w700">{{ $newThisMonth ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. MAIN TABLE SECTION --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-white" style="border-radius: 15px 15px 0 0;">
                        <h4 class="card-title mb-0 fw-bold fs-18">{{ __('head_officers.officer_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table id="officerTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        {{-- Checkbox Column --}}
                                        @can('head_officers.delete')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('head_officers.table_no') }}</th>
                                        <th>{{ __('head_officers.details') }}</th>
                                        <th>{{ __('head_officers.contact') }}</th>
                                        <th>{{ __('head_officers.total_institution') }}</th>
                                        <th class="text-end no-sort">{{ __('head_officers.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- AJAX will load data here --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Add Head Officer Modal --}}
<div class="modal fade" id="addHeaderOfficersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="addForm" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('head_officers.add_head_officer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="{{ __('head_officers.enter_name') }}">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.email') }}</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="{{ __('head_officers.enter_email') }}">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.password') }}</label>
                        <input type="text" name="password" id="password" class="form-control" placeholder="{{ __('head_officers.enter_password') }}">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.phone') }}</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="{{ __('head_officers.enter_phone') }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">{{ __('head_officers.address') }}</label>
                        <textarea class="form-control" rows="3" name="address" id="address" placeholder="{{ __('head_officers.enter_address') }}"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('head_officers.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('head_officers.add') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Head Officer Modal --}}
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
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.name') }}</label>
                        <input type="text" id="edit_name" name="name" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.email') }}</label>
                        <input type="email" id="edit_email" name="email" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.password_optional') }}</label>
                        <input type="text" id="edit_password" name="password" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">{{ __('head_officers.phone') }}</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">{{ __('head_officers.address') }}</label>
                        <textarea id="edit_address" name="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('head_officers.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('head_officers.update') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Scripts --}}
@section('js')
<!-- ================= EXPORT DEPENDENCIES ================= -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<!-- ================= BUTTONS JS ================= -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Initialize DataTable ---
        const table = $('#officerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('header-officers.index') }}",
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text: '<i class="fa fa-download me-1"></i> {{ __("head_officers.export") }}',
                    buttons: [
                        { extend: 'print', text: '<i class="fa fa-print me-2"></i> Print', className: 'dropdown-item' },
                        { extend: 'csv', text: '<i class="fa fa-file-text-o me-2"></i> CSV', className: 'dropdown-item' },
                        { extend: 'excel', text: '<i class="fa fa-file-excel-o me-2"></i> Excel', className: 'dropdown-item' },
                        { extend: 'pdf', text: '<i class="fa fa-file-pdf-o me-2"></i> PDF', className: 'dropdown-item' },
                        { extend: 'copy', text: '<i class="fa fa-copy me-2"></i> Copy', className: 'dropdown-item' }
                    ]
                },
                @can('head_officers.delete')
                {
                    text: '<i class="fa fa-trash me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">{{ __("head_officers.bulk_delete") }}</span>',
                    className: 'bulk-delete-btn btn btn-danger',
                    enabled: false,
                    action: function () {
                        let selectedIds = [];
                        $('.single-checkbox:checked').each(function() {
                            selectedIds.push($(this).val());
                        });
                        handleBulkDelete(selectedIds);
                    }
                }
                @endcan
            ],
            ordering: true,
            order: [[1, 'desc']], 
            columns: [
                @can('head_officers.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id', orderable: true, searchable: false },
                { data: 'details', name: 'name' },
                { data: 'contact', name: 'phone' },
                { data: 'total_institution', name: 'total_institution', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                search: "",
                searchPlaceholder: "{{ __('head_officers.search_placeholder') }}",
                emptyTable: "{{ __('head_officers.no_records_found') }}",
                processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>',
                lengthMenu: "_MENU_",
                paginate: { next: '<i class="fa fa-angle-right"></i>', previous: '<i class="fa fa-angle-left"></i>' }
            },
            drawCallback: function() {
                updateBulkDeleteState();
                $('#checkAll').prop('checked', false);
            }
        });

        // --- 2. Checkbox & Bulk Delete Logic ---
        $('#checkAll').on('click', function() {
            const isChecked = this.checked;
            $('.single-checkbox').prop('checked', isChecked);
            updateBulkDeleteState();
        });

        $('#officerTable').on('change', '.single-checkbox', function() {
            updateBulkDeleteState();
            if ($('.single-checkbox:checked').length === $('.single-checkbox').length) {
                $('#checkAll').prop('checked', true);
            } else {
                $('#checkAll').prop('checked', false);
            }
        });

        function updateBulkDeleteState() {
            const count = $('.single-checkbox:checked').length;
            const btn = table.button('.bulk-delete-btn');
            
            if (count > 0) {
                btn.enable();
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('head_officers.bulk_delete') }} (${count})`);
            } else {
                btn.disable();
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('head_officers.bulk_delete') }}`);
            }
        }

        // --- 3. AJAX Actions (Create/Edit/Delete) ---

        // Add Validation & Submit
        $('#addForm').submit(function(e){
            e.preventDefault();
            let name = $('#name').val();
            let email = $('#email').val();
            let phone = $('#phone').val();
            let password = $('#password').val();
            let address = $('#address').val();

            if(name === '' || email === '' || phone === '' || password === '' || address === ''){
                Swal.fire('{{ __("head_officers.validation") }}', '{{ __("head_officers.error") }}', 'error');
                return;
            }

            $.ajax({
                url:"{{ route('header-officers.store') }}",
                method:"POST",
                data: $(this).serialize(),
                success:function(res){
                    $('#addHeaderOfficersModal').modal('hide');
                    $('#addForm')[0].reset();
                    Swal.fire('{{ __("head_officers.success") }}', res.message, 'success');
                    table.ajax.reload();
                },
                error:function(xhr){
                    let msg = xhr.responseJSON.message || '{{ __("head_officers.something_went_wrong") }}';
                    // If validation errors exist, take the first one
                    if(xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    Swal.fire('{{ __("head_officers.error_occurred") }}', msg, 'error');
                }
            });
        });

        // Load Edit Modal
        $('#officerTable tbody').on('click', '.edit-btn', function () {
            let id = $(this).data('id');
            $.get("{{ url('header-officers') }}/" + id + "/edit", function(res) {
                $('#edit_id').val(res.id);
                $('#edit_name').val(res.name);
                $('#edit_email').val(res.email);
                $('#edit_phone').val(res.phone);
                $('#edit_address').val(res.address);
                $('#edit_password').val(''); // Clear password field
            });
        });

        // Update Submit
        $('#editForm').submit(function(e){
            e.preventDefault();
            let id = $('#edit_id').val();
            $.ajax({
                url: "{{ url('header-officers') }}/" + id,
                method: "POST", // Method spoofing is handled by @method('PUT') inside form if using FormData, or just POST if Laravel handles it. Here we used serialzie which includes _method=PUT
                data: $(this).serialize(),
                success: function(res){
                    $('#editHeaderOfficerModal').modal('hide');
                    Swal.fire('{{ __("head_officers.success") }}', res.message, 'success');
                    table.ajax.reload();
                },
                error: function(xhr){
                    let msg = xhr.responseJSON.message || '{{ __("head_officers.something_went_wrong") }}';
                    if(xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    Swal.fire('{{ __("head_officers.error_occurred") }}', msg, 'error');
                }
            });
        });

        // Single Delete
        $('#officerTable tbody').on('click', '.delete-btn', function () {
            let id = $(this).data('id');
            Swal.fire({
                title: "{{ __('head_officers.are_you_sure') }}",
                text: "{{ __('head_officers.officer_deleted_warning') }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('head_officers.yes_delete') }}",
                cancelButtonText: "{{ __('head_officers.cancel') }}"
            }).then(result => {
                if(result.isConfirmed){
                    $.ajax({
                        url: "{{ url('header-officers') }}/" + id,
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: function(res){
                            Swal.fire('{{ __("head_officers.success") }}', res.message, 'success');
                            table.ajax.reload();
                        },
                        error: function(){
                            Swal.fire('{{ __("head_officers.error_occurred") }}', '{{ __("head_officers.something_went_wrong") }}', 'error');
                        }
                    });
                }
            });
        });

        // Bulk Delete
        function handleBulkDelete(ids) {
            if (ids.length === 0) return;
            Swal.fire({
                title: "{{ __('head_officers.are_you_sure_bulk') }}",
                text: "{{ __('head_officers.bulk_delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('head_officers.yes_bulk_delete') }}",
                cancelButtonText: "{{ __('head_officers.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('header-officers.bulkDelete') }}", // Ensure you add this route
                        type: 'POST',
                        data: { ids: ids, _token: "{{ csrf_token() }}" },
                        success: function(response) {
                            if(response.success) {
                                Swal.fire("{{ __('head_officers.success') }}", response.success, 'success');
                                table.ajax.reload();
                                $('#checkAll').prop('checked', false);
                            } else {
                                Swal.fire("{{ __('head_officers.error_occurred') }}", "{{ __('head_officers.something_went_wrong') }}", 'error');
                            }
                        },
                        error: function() {
                            Swal.fire("{{ __('head_officers.error_occurred') }}", "{{ __('head_officers.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        }
    });
</script>
@endsection