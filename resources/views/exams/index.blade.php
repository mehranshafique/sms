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
                    <h4>{{ __('exam.page_title') }}</h4>
                    <p class="mb-0">{{ __('exam.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('exam.create')
                <a href="{{ route('exams.create') }}" class="btn btn-primary btn-rounded">
                    <i class="fa fa-plus me-2"></i> {{ __('exam.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('exam.exam_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="examTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        @can('exam.deleteAny')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('exam.table_no') }}</th>
                                        <th>{{ __('exam.exam_name') }}</th>
                                        <th>{{ __('exam.category') }}</th> <!-- Added Category Column -->
                                        <th>{{ __('exam.session') }}</th>
                                        <th>{{ __('exam.start_date') }}</th>
                                        <th>{{ __('exam.status') }}</th>
                                        <th class="text-end">{{ __('exam.action') }}</th>
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
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#examTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('exams.index') }}",
            columns: [
                @can('exam.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'category', name: 'category' }, // Added Category
                { data: 'session', name: 'academicSession.name' },
                { data: 'start_date', name: 'start_date' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Add Bulk Delete Logic here (similar to previous modules)
        $('#checkAll').on('click', function() {
            $('.single-checkbox').prop('checked', this.checked);
        });
    });
</script>
@endsection