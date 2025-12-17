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
                    <h4>{{ __('finance.fee_type_title') }}</h4>
                    <p class="mb-0">{{ __('finance.manage_types_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <button type="button" class="btn btn-primary btn-rounded" data-bs-toggle="modal" data-bs-target="#addFeeTypeModal">
                    <i class="fa fa-plus me-2"></i> {{ __('finance.add_type') }}
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('finance.fee_type_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="feeTypeTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('finance.type_name') }}</th>
                                        <th>{{ __('finance.description') }}</th>
                                        <th>{{ __('finance.status') }}</th>
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

{{-- Add/Edit Modal --}}
<div class="modal fade" id="addFeeTypeModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">{{ __('finance.add_type') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('fee-types.store') }}" method="POST" id="feeTypeForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('finance.type_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Tuition, Transport">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('finance.description') }}</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('finance.status') }}</label>
                        <select name="is_active" id="is_active" class="form-control default-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">{{ __('finance.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('finance.save') }}</button>
                </div>
            </form>
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
        var table = $('#feeTypeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('fee-types.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'description', name: 'description' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Edit Button Click
        $('#feeTypeTable').on('click', '.edit-btn', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let desc = $(this).data('description');
            let status = $(this).data('status');

            $('#modalTitle').text("{{ __('finance.edit_type') }}");
            $('#feeTypeForm').attr('action', "{{ route('fee-types.index') }}/" + id);
            $('#formMethod').val('PUT');
            
            $('#name').val(name);
            $('#description').val(desc);
            $('#is_active').val(status).trigger('change');

            $('#addFeeTypeModal').modal('show');
        });

        // Reset Modal on Close
        $('#addFeeTypeModal').on('hidden.bs.modal', function () {
            $('#modalTitle').text("{{ __('finance.add_type') }}");
            $('#feeTypeForm').attr('action', "{{ route('fee-types.store') }}");
            $('#formMethod').val('POST');
            $('#feeTypeForm')[0].reset();
            $('#is_active').val(1).trigger('change');
        });

        // Form Submit
        $('#feeTypeForm').submit(function(e){
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    $('#addFeeTypeModal').modal('hide');
                    Swal.fire('Success', response.message, 'success');
                    table.ajax.reload();
                },
                error: function(xhr){
                    let msg = 'Error occurred';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // Delete Logic (Standard)
        $('#feeTypeTable').on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('fee-types.destroy', ':id') }}".replace(':id', id);
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        success: function(response) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        }
                    });
                }
            });
        });
    });
</script>
@endsection