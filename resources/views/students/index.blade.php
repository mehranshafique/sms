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
                    <h4>{{ __('student.student_management') }}</h4>
                    <p class="mb-0">{{ __('student.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('students.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('student.create_new') }}
                </a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label" for="filter_grade">{{ __('grade_level.grade_name') }}</label>
                <select id="filter_grade" class="form-control default-select">
                    <option value="">{{ __('timetable.all_grades') }}</option>
                    @foreach($gradeLevels as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="filter_section">{{ __('student.select_section') }}</label>
                <select id="filter_section" class="form-control default-select">
                    <option value="">{{ __('finance.all_sections') }}</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('student.student_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="studentTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('student.table_no') }}</th>
                                        <th>{{ __('student.details') }}</th>
                                        <th>{{ __('student.parent_info') }}</th>
                                        <th>{{ __('student.class_grade') }}</th>
                                        <th>{{ __('student.status') }}</th>
                                        <th class="text-end">{{ __('student.action') }}</th>
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
        // 1. Initialize DataTable
        var table = $('#studentTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('students.index') }}",
                data: function (d) {
                    d.grade_level_id = $('#filter_grade').val();
                    d.class_section_id = $('#filter_section').val();
                }
            },
            language: {
                search: "{{ __('pagination.search') }}",
                lengthMenu: "{{ __('pagination.show') }} _MENU_ {{ __('pagination.entries') }}",
                info: "{{ __('pagination.info') }}",
                infoEmpty: "{{ __('pagination.info_empty') }}",
                infoFiltered: "{{ __('pagination.info_filtered') }}",
                zeroRecords: "{{ __('pagination.zero_records') }}",
                emptyTable: "{{ __('pagination.empty_table') }}",
                processing: "{{ __('pagination.processing') }}",
            },
            columns: [
                { data: 'DT_RowIndex', name: 'students.id' },
                { data: 'details', name: 'students.first_name' },
                { data: 'parent_info', name: 'parents.father_name' },
                { data: 'class', name: 'classSection.name', orderable: false },
                { data: 'status', name: 'students.status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        $('#filter_grade').on('change', function () {
            var gradeId = $(this).val();
            var $section = $('#filter_section');

            $section.html('<option value="">{{ __('student.loading') }}</option>');

            if (!gradeId) {
                $section.html('<option value="">{{ __('finance.all_sections') }}</option>');
                if ($section.data('selectpicker')) {
                    $section.selectpicker('refresh');
                }
                table.draw();
                return;
            }

            fetch("{{ route('students.get_sections') }}?grade_id=" + encodeURIComponent(gradeId))
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    var html = '<option value="">{{ __('finance.all_sections') }}</option>';
                    Object.keys(data).forEach(function (id) {
                        html += '<option value="' + id + '">' + data[id] + '</option>';
                    });
                    $section.html(html);
                    if ($section.data('selectpicker')) {
                        $section.selectpicker('destroy');
                    }
                    if (typeof window.digitexReinitSelectPickers === 'function') {
                        window.digitexReinitSelectPickers();
                    }
                    table.draw();
                })
                .catch(function () {
                    $section.html('<option value="">{{ __('student.error_loading') }}</option>');
                });
        });

        $('#filter_section').on('change', function () {
            table.draw();
        });

        // 2. Delete Button Logic
        $('#studentTable').on('click', '.delete-btn', function() {
            var id = $(this).data('id');
            var deleteUrl = "{{ route('students.destroy', ':id') }}";
            deleteUrl = deleteUrl.replace(':id', id);

            Swal.fire({
                title: "{{ __('student.are_you_sure') }}",
                text: "{{ __('student.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: "{{ __('student.yes_delete') }}",
                cancelButtonText: "{{ __('student.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            table.ajax.reload();
                            Swal.fire(
                                "{{ __('student.deleted') }}",
                                response.message,
                                'success'
                            );
                        },
                        error: function(xhr) {
                            Swal.fire(
                                "{{ __('student.error') }}",
                                "{{ __('student.something_went_wrong') }}",
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@endsection