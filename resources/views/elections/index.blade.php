@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        .blink { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0.5; } }
    </style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('voting.election_management') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('voting.manage_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('election.create')
                <a href="{{ route('elections.create') }}" class="btn btn-primary btn-rounded shadow-sm fw-bold px-4 py-2">
                    <i class="fa fa-plus me-2"></i> {{ __('voting.create_election') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body px-4 pb-4 pt-4">
                        <div class="table-responsive">
                            <table id="electionTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('voting.title') }}</th>
                                        <th>{{ __('voting.start_date') }}</th>
                                        <th>{{ __('voting.end_date') }}</th>
                                        <th>{{ __('voting.status') }}</th>
                                        <th>{{ __('voting.candidates_count') }}</th>
                                        <th class="text-end">{{ __('voting.actions') }}</th>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#electionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('elections.index') }}",
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [],
            order: [[2, 'desc']], // Sort by Start Date by default
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'title', name: 'title' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'status', name: 'status' },
                { data: 'candidates_count', name: 'candidates_count', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>',
                paginate: { next: '<i class="fa fa-angle-right"></i>', previous: '<i class="fa fa-angle-left"></i>' }
            }
        });

        $('#electionTable tbody').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('elections.destroy', ':id') }}".replace(':id', id);
            Swal.fire({
                title: "{{ __('voting.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('voting.yes_delete') }}",
                cancelButtonText: "{{ __('voting.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('voting.success') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('voting.error') }}", "{{ __('voting.system_error') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection