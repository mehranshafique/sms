@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('attendance.analytics_title') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('attendance.select_student_subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-bottom-0 pb-0">
                        <h4 class="card-title">{{ __('attendance.student_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="analyticsTable" class="display table table-striped table-hover table-bordered" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('attendance.admission_no') }}</th>
                                        <th>{{ __('attendance.student_name') }}</th>
                                        <th>{{ __('attendance.class') }}</th>
                                        <th class="text-end">{{ __('attendance.action') }}</th>
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
        var table = $('#analyticsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('attendance.analytics.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'admission_number', name: 'admission_number' },
                { data: 'name', name: 'first_name' }, // Searches first_name logically via controller concat
                { data: 'class', name: 'classSection.name', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                paginate: {
                    next: '<i class="fa fa-angle-double-right" aria-hidden="true"></i>',
                    previous: '<i class="fa fa-angle-double-left" aria-hidden="true"></i>' 
                }
            }
        });
    });
</script>
@endsection