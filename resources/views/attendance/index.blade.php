@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
    <style>
        .dt-buttons .dropdown-toggle { background-color: #fff !important; color: #697a8d !important; border-color: #d9dee3 !important; border-radius: 0.375rem; padding: 0.4375rem 1rem; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #d9dee3; border-radius: 0.375rem; padding: 0.4375rem 0.875rem; margin-left: 0.5em; outline: none; }
    </style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('attendance.attendance_management') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('attendance.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                {{-- View Register Button --}}
                <a href="{{ route('attendance.report') }}" class="btn btn-secondary btn-rounded shadow-sm fw-bold px-4 py-2">
                    <i class="fa fa-list-alt me-2"></i> {{ __('attendance.view_register') ?? 'View Register' }}
                </a>

                @can('student_attendance.create')
                <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-rounded shadow-sm fw-bold px-4 py-2">
                    <i class="fa fa-plus me-2"></i> {{ __('attendance.mark_attendance') }}
                </a>
                @endcan
            </div>
        </div>

        {{-- Filters --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <select id="filter_class" class="form-control default-select shadow-sm">
                    <option value="">{{ __('attendance.filter_class') }}</option>
                    @foreach($classSections as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                {{-- Fixed: Date Picker Style --}}
                <input type="text" id="filter_date" class="form-control datepicker shadow-sm" value="{{ date('Y-m-d') }}" placeholder="YYYY-MM-DD">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-white">
                        <h4 class="card-title mb-0 fw-bold fs-18">{{ __('attendance.attendance_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
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
        
        // Fix: Force refresh of selectpickers on load
        if(jQuery().selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

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
            dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text: '<i class="fa fa-download me-1"></i> {{ __("attendance.export") }}',
                    buttons: [
                        { extend: 'print', className: 'dropdown-item', text: 'Print' },
                        { extend: 'excel', className: 'dropdown-item', text: 'Excel' },
                        { extend: 'pdf', className: 'dropdown-item', text: 'PDF' }
                    ]
                }
            ],
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