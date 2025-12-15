@extends('layout.layout')

@section('styles')
    {{-- DataTables Buttons & Select CSS --}}
   
    <style>
        /* Custom Adjustments for DataTables */
        .dt-button-collection {
            z-index: 2001 !important;
        }
        
        /* Institute Icon in Table Details */
        .institute-icon {
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
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('institute.institute_management') }}</h4>
                    <p class="mb-0">{{ __('institute.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('institute.create')
                <a href="{{ route('institutes.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('institute.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        {{-- 2. STATS CARDS (Single Row) --}}
        <div class="row">
            {{-- Total Institutes --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary">
                                <i class="la la-building"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('institute.total_institutes') }}</p>
                                <h4 class="mb-0">{{ $totalInstitutes ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Institutes --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success">
                                <i class="la la-check-circle"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('institute.active_institutes') }}</p>
                                <h4 class="mb-0">{{ $activeInstitutes ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Inactive Institutes --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-danger text-danger">
                                <i class="la la-times-circle"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('institute.inactive') }}</p>
                                <h4 class="mb-0">{{ $inactiveInstitutes ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- New This Month --}}
            <div class="col-xl-3 col-xxl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-info text-info">
                                <i class="la la-calendar-plus-o"></i>
                            </span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('institute.new_this_month') }}</p>
                                <h4 class="mb-0">{{ $newInstitutes ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. MAIN TABLE SECTION --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('institute.institute_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="instituteTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        {{-- Checkbox Column --}}
                                        @can('institute.delete')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('institute.id') }}</th>
                                        <th>{{ __('institute.details') }}</th>
                                        <th>{{ __('institute.type') }}</th>
                                        <th>{{ __('institute.contact') }}</th>
                                        <th>{{ __('institute.status') }}</th>
                                        <th class="text-end no-sort">{{ __('institute.action') }}</th>
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
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is required for DataTables.');
            return;
        }

        const table = $('#instituteTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('institutes.index') }}",
            
            // DOM Configuration adapted for Bootstrap Layout
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text: '<i class="fa fa-download me-1"></i> {{ __("institute.export") }}',
                    buttons: [
                        { extend: 'print', text: '<i class="fa fa-print me-2"></i> Print', className: 'dropdown-item' },
                        { extend: 'csv', text: '<i class="fa fa-file-text-o me-2"></i> CSV', className: 'dropdown-item' },
                        { extend: 'excel', text: '<i class="fa fa-file-excel-o me-2"></i> Excel', className: 'dropdown-item' },
                        { extend: 'pdf', text: '<i class="fa fa-file-pdf-o me-2"></i> PDF', className: 'dropdown-item' },
                        { extend: 'copy', text: '<i class="fa fa-copy me-2"></i> Copy', className: 'dropdown-item' }
                    ]
                },
                @can('institute.delete')
                {
                    text: '<i class="fa fa-trash me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">{{ __("institute.bulk_delete") }}</span>',
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
                @can('institute.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'id_display', name: 'code' },
                
                // Modified Details Column: Using render to add Icon/Image with Theme classes
                { 
                    data: 'name', 
                    name: 'name',
                    render: function(data, type, row) {
                        let initial = data.charAt(0).toUpperCase();
                        let email = row.email ? row.email : ''; 
                        
                        // Using 'bgl-primary text-primary' classes from your theme
                        return `
                        <div class="d-flex align-items-center">
                            <div class="institute-icon bgl-primary text-primary">
                                ${initial}
                            </div>
                            <div>
                                <h6 class="fs-16 font-w600 mb-0"><a href="#" class="text-black">${data}</a></h6>
                                <span class="fs-13 text-muted">${email}</span>
                            </div>
                        </div>`;
                    }
                },
                { data: 'type', name: 'type' },
                { data: 'contact', name: 'city' }, 
                { data: 'status', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            
            language: {
                search: "",
                searchPlaceholder: "{{ __('institute.search_placeholder') }}",
                emptyTable: "{{ __('institute.no_records_found') }}",
                processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>',
                lengthMenu: "_MENU_",
                paginate: {
                    next: '<i class="fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i>' 
                }
            },
            
            drawCallback: function() {
                updateBulkDeleteState();
                $('#checkAll').prop('checked', false);
            }
        });

        // --- Bulk Selection Logic ---
        $('#checkAll').on('click', function() {
            const isChecked = this.checked;
            $('.single-checkbox').prop('checked', isChecked);
            updateBulkDeleteState();
        });

        $('#instituteTable').on('change', '.single-checkbox', function() {
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
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('institute.bulk_delete') }} (${count})`);
            } else {
                btn.disable();
                $(btn.node()).html(`<i class="fa fa-trash me-1"></i> {{ __('institute.bulk_delete') }}`);
            }
        }

        // --- Bulk Delete Action ---
        function handleBulkDelete(ids) {
            if (ids.length === 0) return;

            Swal.fire({
                title: "{{ __('institute.are_you_sure_bulk') }}",
                text: "{{ __('institute.bulk_delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('institute.yes_bulk_delete') }}",
                cancelButtonText: "{{ __('institute.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('institutes.bulkDelete') }}",
                        type: 'POST',
                        data: { ids: ids, _token: "{{ csrf_token() }}" },
                        success: function(response) {
                            if(response.success) {
                                Swal.fire("{{ __('institute.success') }}", response.success, 'success');
                                table.ajax.reload();
                                $('#checkAll').prop('checked', false);
                            } else {
                                Swal.fire("{{ __('institute.error_occurred') }}", "{{ __('institute.something_went_wrong') }}", 'error');
                            }
                        },
                        error: function() {
                            Swal.fire("{{ __('institute.error_occurred') }}", "{{ __('institute.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        }

        // --- Single Delete Action ---
        $('#instituteTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('institutes.destroy', ':id') }}".replace(':id', id);

            Swal.fire({
                title: "{{ __('institute.are_you_sure') }}",
                text: "{{ __('institute.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('institute.yes_delete') }}",
                cancelButtonText: "{{ __('institute.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function() {
                            Swal.fire("{{ __('institute.success') }}", "{{ __('institute.messages.success_delete') }}", 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('institute.error_occurred') }}", "{{ __('institute.something_went_wrong') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection