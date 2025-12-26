@extends('layout.layout')

@section('styles')
    {{-- Copying styles from Subject index --}}
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
    
    <style>
        .dt-buttons .dropdown-toggle { background-color: #fff !important; color: #697a8d !important; border-color: #d9dee3 !important; }
        .dt-buttons .dropdown-toggle:hover { background-color: #f8f9fa !important; }
        .dt-buttons .btn-danger { background-color: #ff3e1d !important; border-color: #ff3e1d !important; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #d9dee3; padding: 0.4375rem 0.875rem; border-radius: 0.375rem; }
    </style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- TITLE BAR --}}
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('notice.page_title') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('notice.manage_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('notice.create')
                <a href="{{ route('notices.create') }}" class="btn btn-primary btn-rounded shadow-sm fw-bold px-4 py-2">
                    <i class="fa fa-plus me-2"></i> {{ __('notice.add_notice') }}
                </a>
                @endcan
            </div>
        </div>

        {{-- STATS CARDS --}}
        <div class="row">
            <div class="col-xl-6 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary"><i class="la la-bullhorn"></i></span>
                            <div class="media-body">
                                <p class="mb-1">Total Notices</p>
                                <h4 class="mb-0">{{ $totalNotices ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success"><i class="la la-check-circle"></i></span>
                            <div class="media-body">
                                <p class="mb-1">Active / Published</p>
                                <h4 class="mb-0">{{ $activeNotices ?? 0 }}</h4>
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
                        <h4 class="card-title mb-0 fw-bold fs-18">{{ __('notice.notice_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table id="noticeTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('notice.title') }}</th>
                                        <th>{{ __('notice.type') }}</th>
                                        <th>{{ __('notice.audience') }}</th>
                                        <th>{{ __('notice.status') }}</th>
                                        <th>{{ __('notice.published_at') }}</th>
                                        <th class="text-end">{{ __('subject.action') }}</th>
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
{{-- DataTables Dependencies --}}
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#noticeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('notices.index') }}",
            // Matching the Subject Table DOM layout
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [],
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'title', name: 'title' },
                { data: 'type', name: 'type' },
                { data: 'audience', name: 'audience' },
                { data: 'status', name: 'status' },
                { data: 'published_at', name: 'published_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>',
                paginate: { next: '<i class="fa fa-angle-right"></i>', previous: '<i class="fa fa-angle-left"></i>' }
            }
        });

        // Delete Logic
        $('#noticeTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('notices.destroy', ':id') }}".replace(':id', id);
            Swal.fire({
                title: "{{ __('notice.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('notice.yes_delete') }}",
                cancelButtonText: "{{ __('notice.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('notice.success_delete') }}", response.message, 'success');
                            table.ajax.reload();
                        }
                    });
                }
            });
        });
    });
</script>
@endsection