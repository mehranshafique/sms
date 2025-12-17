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
                    <h4>{{ __('attendance.attendance_management') }}</h4>
                    <p class="mb-0">{{ __('attendance.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('attendance.mark_attendance') }}
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <select id="filter_class" class="form-control default-select">
                    <option value="">{{ __('attendance.filter_class') }}</option>
                    @foreach($classSections as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" id="filter_date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('attendance.attendance_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="attendanceTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('attendance.table_no') }}</th>
                                        <th>{{ __('attendance.date') }}</th>
                                        <th>{{ __('attendance.student') }}</th>
                                        <th>{{ __('attendance.roll_no') }}</th>
                                        <th>{{ __('attendance.class') }}</th>
                                        <th>{{ __('attendance.status') }}</th>
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#attendanceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('attendance.index') }}",
                data: function(d) {
                    d.class_section_id = $('#filter_class').val();
                    d.attendance_date = $('#filter_date').val();
                }
            },
            dom: 'Bfrtip',
            buttons: ['csv', 'excel', 'pdf', 'print'],
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'attendance_date', name: 'attendance_date' },
                { data: 'student_name', name: 'student.first_name' },
                { data: 'roll_no', name: 'student.admission_number' },
                { data: 'class', name: 'classSection.name' },
                { data: 'status', name: 'status' },
            ]
        });

        $('#filter_class, #filter_date').on('change', function() {
            table.draw();
        });
    });
</script>
@endsection