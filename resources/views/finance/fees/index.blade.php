@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('finance.fee_structure_title') }}</h4>
                    <p class="mb-0">{{ __('finance.manage_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('fee_structure.create')
                <a href="{{ route('fees.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('finance.add_fee') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4">
                        <h4 class="card-title">{{ __('finance.fee_list') }}</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="feeTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('finance.fee_name') }}</th>
                                        <th>{{ __('finance.fee_type') }}</th>
                                        <th>{{ __('finance.amount') }}</th>
                                        <th>{{ __('finance.frequency') }}</th>
                                        <th>{{ __('finance.grade_level') }}</th>
                                        <th class="text-end">{{ __('finance.action') }}</th>
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
    $(document).ready(function() {
        const table = $('#feeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('fees.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'fee_type', name: 'feeType.name' },
                { data: 'amount', name: 'amount' },
                { data: 'frequency', name: 'frequency' },
                { data: 'grade', name: 'gradeLevel.name' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                paginate: {
                    next: '<i class="fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i>'
                }
            }
        });

        // Delete Handler
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('fees.destroy', ':id') }}".replace(':id', id);
            
            Swal.fire({
                title: "{{ __('finance.are_you_sure') }}",
                text: "{{ __('finance.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: "{{ __('finance.yes_delete') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        success: function(response) {
                            Swal.fire("{{ __('finance.deleted') }}", response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('finance.error') }}", "{{ __('finance.error_occurred') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection