@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('discipline.page_title') }}</h4>
                    <p class="mb-0">{{ __('discipline.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <select id="statusFilter" class="form-control default-select bg-white shadow-sm w-auto">
                    <option value="all" selected>{{ __('discipline.filter_all') }}</option>
                    @foreach(\App\Models\DisciplinaryRecord::STATUSES as $st)
                        <option value="{{ $st }}">{{ __('discipline.status_' . $st) }}</option>
                    @endforeach
                </select>
                <select id="typeFilter" class="form-control default-select bg-white shadow-sm w-auto">
                    <option value="all" selected>{{ __('discipline.filter_all') }}</option>
                    @foreach(\App\Models\DisciplinaryRecord::TYPES as $tp)
                        <option value="{{ $tp }}">{{ __('discipline.type_' . $tp) }}</option>
                    @endforeach
                </select>
                @can('create', \App\Models\DisciplinaryRecord::class)
                <a href="{{ route('discipline.create') }}" class="btn btn-primary shadow-sm">
                    <i class="fa fa-plus me-2"></i> {{ __('discipline.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="disciplineTable" class="display table table-striped table-hover" style="width:100%; min-width: 900px;">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('discipline.reference') }}</th>
                                        <th>{{ __('discipline.student') }}</th>
                                        <th>{{ __('discipline.incident_type') }}</th>
                                        <th>{{ __('discipline.incident_date') }}</th>
                                        <th>{{ __('discipline.severity') }}</th>
                                        <th>{{ __('discipline.status') }}</th>
                                        <th class="text-end">{{ __('discipline.action') }}</th>
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

<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">{{ __('discipline.update_status') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('discipline.status') }}</label>
                        <select name="status" id="statusSelect" class="form-control" required>
                            @foreach(\App\Models\DisciplinaryRecord::STATUSES as $st)
                                <option value="{{ $st }}">{{ __('discipline.status_' . $st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('discipline.action_taken') }}</label>
                        <textarea name="action_taken" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('budget.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('budget.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    var table = $('#disciplineTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('discipline.index') }}",
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.incident_type = $('#typeFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'reference', name: 'reference_no' },
            { data: 'student_name', name: 'student.first_name' },
            { data: 'incident_type_label', name: 'incident_type' },
            { data: 'incident_date', name: 'incident_date' },
            { data: 'severity', name: 'severity' },
            { data: 'status', name: 'status' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#statusFilter, #typeFilter').on('change', function() { table.ajax.reload(); });

    var recordId = null;
    $(document).on('click', '.update-status-btn', function() {
        recordId = $(this).data('id');
        $('#statusSelect').val($(this).data('status'));
        $('#statusModal').modal('show');
    });

    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        $.post("{{ url('discipline') }}/" + recordId + "/status", $(this).serialize())
            .done(function(resp) {
                $('#statusModal').modal('hide');
                table.ajax.reload();
                Swal.fire('{{ __('configuration.success') }}', resp.message, 'success');
            })
            .fail(function(xhr) {
                Swal.fire('{{ __('configuration.error') }}', xhr.responseJSON?.message || 'Error', 'error');
            });
    });

    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: '{{ __('discipline.confirm_delete') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('discipline') }}/" + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp) {
                    table.ajax.reload();
                    Swal.fire('{{ __('configuration.success') }}', resp.message, 'success');
                }
            });
        });
    });
});
</script>
@endsection
