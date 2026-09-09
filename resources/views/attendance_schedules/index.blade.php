@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('attendance_schedule.list_title') }}</h4>
                    <p class="mb-0">{{ __('attendance_schedule.page_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('create', App\Models\AttendanceSchedule::class)
                <a href="{{ route('attendance-schedules.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('attendance_schedule.add_new') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary"><i class="la la-clock"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('attendance_schedule.total') }}</p>
                                <h4 class="mb-0">{{ $total ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success"><i class="la la-check-circle"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('attendance_schedule.active_count') }}</p>
                                <h4 class="mb-0">{{ $active ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="schedulesTable" class="display table" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('attendance_schedule.name') }}</th>
                                        <th>{{ __('attendance_schedule.check_in_time') }}</th>
                                        <th>{{ __('attendance_schedule.check_out_time') }}</th>
                                        <th>{{ __('attendance_schedule.late_margin_minutes') }}</th>
                                        <th>{{ __('attendance_schedule.assignments') }}</th>
                                        <th>{{ __('attendance_schedule.status') }}</th>
                                        <th class="text-end">{{ __('attendance.action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <p class="text-muted small mt-3 mb-0">{{ __('attendance_schedule.fallback_note') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    var table = $('#schedulesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('attendance-schedules.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'check_in', name: 'check_in_time', orderable: false, searchable: false },
            { data: 'check_out', name: 'check_out_time', orderable: false, searchable: false },
            { data: 'late_margin', name: 'late_margin_minutes', searchable: false },
            { data: 'assignments_summary', name: 'assignments_summary', orderable: false, searchable: false },
            { data: 'is_active', name: 'is_active' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '.delete-btn', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '{{ __("attendance_schedule.confirm_delete") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("attendance_schedule.yes_delete") }}',
            cancelButtonText: '{{ __("attendance_schedule.cancel") }}'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('attendance-schedules') }}/" + id,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function (res) {
                    Swal.fire('{{ __("attendance_schedule.success") }}', res.message, 'success');
                    table.ajax.reload();
                },
                error: function () {
                    Swal.fire('{{ __("attendance_schedule.validation_error") }}', '{{ __("attendance_schedule.error_occurred") }}', 'error');
                }
            });
        });
    });
});
</script>
@endsection
