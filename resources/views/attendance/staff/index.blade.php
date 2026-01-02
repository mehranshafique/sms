@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('staff.attendance_title') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('staff-attendance.create') }}" class="btn btn-primary">{{ __('staff.mark_attendance') }}</a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('staff.attendance_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="staffAttendanceTable" class="display" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>{{ __('staff.table_no') }}</th>
                                        <th>{{ __('staff.full_name') }}</th>
                                        <th>{{ __('staff.employee_id') }}</th>
                                        <th>{{ __('staff.date') }}</th>
                                        <th>{{ __('staff.status_label') }}</th>
                                        <th>{{ __('staff.check_in') }}</th>
                                        <th>{{ __('staff.check_out') }}</th>
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
<script>
    $(document).ready(function() {
        $('#staffAttendanceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('staff-attendance.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'staff_name', name: 'staff.first_name' },
                { data: 'staff_id', name: 'staff.employee_id' },
                { data: 'attendance_date', name: 'attendance_date' },
                { data: 'status', name: 'status' },
                { data: 'check_in', name: 'check_in' },
                { data: 'check_out', name: 'check_out' }
            ],
            language: {
                paginate: { next: '<i class="fa fa-angle-right"></i>', previous: '<i class="fa fa-angle-left"></i>' }
            }
        });
    });
</script>
@endsection