@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('university_enrollment.page_title') }}</h4>
                    <p class="mb-0">{{ __('university_enrollment.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @if(auth()->user()->can('university_enrollment.create'))
                <a href="{{ route('university.enrollments.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-2"></i> {{ __('university_enrollment.create_new') }}
                </a>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="enrollmentTable" class="display" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('university_enrollment.student_name') }}</th>
                                        <th>{{ __('university_enrollment.admission_no') }}</th>
                                        <th>{{ __('university_enrollment.program') }}</th>
                                        <th>{{ __('university_enrollment.level') }}</th>
                                        <th>{{ __('university_enrollment.status') }}</th>
                                        <th>{{ __('university_enrollment.actions') }}</th>
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
        var table = $('#enrollmentTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('university.enrollments.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'student_name', name: 'student.first_name' },
                { data: 'admission_no', name: 'student.admission_number' },
                { data: 'program', name: 'classSection.name' },
                { data: 'level', name: 'classSection.gradeLevel.name' },
                { data: 'status', name: 'status' },
                { data: 'action', orderable: false, searchable: false }
            ]
        });

        // Delete Handler
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: '{{ __('university_enrollment.confirm_delete') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: '{{ __('university_enrollment.delete') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('university/enrollments') }}/" + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            table.ajax.reload();
                        }
                    });
                }
            });
        });
    });
</script>
@endsection