@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('lmd.programs_page_title') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-0 text-end">
                <button type="button" class="btn btn-primary shadow btn-sm" data-bs-toggle="modal" data-bs-target="#programModal">
                    <i class="fa fa-plus me-2"></i> {{ __('lmd.create_program') }}
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="programsTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('lmd.program_code') }}</th>
                                        <th>{{ __('lmd.program_name') }}</th>
                                        <th>{{ __('subject.department') }}</th>
                                        <th>{{ __('lmd.total_semesters') }}</th>
                                        <th>{{ __('lmd.duration_years') }}</th>
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

<div class="modal fade" id="programModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="programForm" action="{{ route('programs.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="programId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">{{ __('lmd.create_program') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('lmd.program_name') }} *</label>
                        <input type="text" name="name" id="programName" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('lmd.program_code') }} *</label>
                            <input type="text" name="code" id="programCode" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('subject.department') }}</label>
                            <select name="department_id" id="deptSelect" class="form-control default-select">
                                <option value="">-- {{ __('subject.select_department') }} --</option>
                                @foreach($departments as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('lmd.total_semesters') }} *</label>
                            <input type="number" name="total_semesters" id="semesters" class="form-control" value="8" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('lmd.duration_years') }} *</label>
                            <input type="number" name="duration_years" id="duration" class="form-control" value="4" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('finance.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('finance.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        var table = $('#programsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('programs.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'code' },
                { data: 'name' },
                { data: 'department_name', name: 'department.name' },
                { data: 'total_semesters' },
                { data: 'duration_years' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Edit
        $(document).on('click', '.edit-program', function() {
            var data = $(this).data('json');
            $('#programId').val(data.id);
            $('#programName').val(data.name);
            $('#programCode').val(data.code);
            $('#deptSelect').val(data.department_id).change();
            $('#semesters').val(data.total_semesters);
            $('#duration').val(data.duration_years);
            $('#modalTitle').text("{{ __('lmd.edit_program') }}");
            $('#programModal').modal('show');
            if($.fn.selectpicker) $('.default-select').selectpicker('refresh');
        });

        // Reset
        $('#programModal').on('hidden.bs.modal', function () {
            $('#programForm')[0].reset();
            $('#programId').val('');
            $('#modalTitle').text("{{ __('lmd.create_program') }}");
            if($.fn.selectpicker) $('.default-select').selectpicker('refresh');
        });

         // Generic Form Submit
        $('#programForm').submit(function(e) {
            e.preventDefault();
            var btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#programModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(res.message);
                    btn.prop('disabled', false);
                },
                error: function(err) {
                    btn.prop('disabled', false);
                    alert('Error saving program.');
                }
            });
        });
        
        // Delete
        $(document).on('click', '.delete-program', function() {
            if(!confirm('Delete this program?')) return;
            var id = $(this).data('id');
            $.ajax({
                url: '/academics/programs/' + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    table.ajax.reload();
                    toastr.success(res.message);
                }
            });
        });
    });
</script>
@endsection