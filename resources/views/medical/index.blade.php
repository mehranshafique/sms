@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('medical.page_title') }}</h4>
                    <p class="mb-0">{{ __('medical.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                @if(auth()->user()->hasRole(['Super Admin', 'School Admin', 'Head Officer']) || auth()->user()->can('medical_record.create'))
                <a href="{{ route('medical-records.visits.create') }}" class="btn btn-primary shadow-sm">
                    <i class="fa fa-plus me-2"></i> {{ __('medical.record_visit') }}
                </a>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <span class="me-3 text-primary fs-2"><i class="fa fa-notes-medical"></i></span>
                        <div>
                            <h3 class="mb-0">{{ $stats['today'] }}</h3>
                            <small class="text-muted">{{ __('medical.visits_today') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <span class="me-3 text-info fs-2"><i class="fa fa-calendar-week"></i></span>
                        <div>
                            <h3 class="mb-0">{{ $stats['week'] }}</h3>
                            <small class="text-muted">{{ __('medical.visits_week') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <span class="me-3 text-danger fs-2"><i class="fa fa-house-medical"></i></span>
                        <div>
                            <h3 class="mb-0">{{ $stats['sent_home'] }}</h3>
                            <small class="text-muted">{{ __('medical.sent_home_month') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('medical.find_student') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-2">
                            <input type="text" id="studentSearch" class="form-control" placeholder="{{ __('medical.search_placeholder') }}">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                        </div>
                        <div id="studentResults" class="list-group"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ __('medical.visit_log') }}</h4>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="date" id="fromFilter" class="form-control w-auto">
                            <input type="date" id="toFilter" class="form-control w-auto">
                            <select id="outcomeFilter" class="form-control w-auto">
                                <option value="all">{{ __('medical.all_outcomes') }}</option>
                                @foreach(\App\Models\InfirmaryVisit::OUTCOMES as $outcome)
                                    <option value="{{ $outcome }}">{{ __('medical.outcome_' . $outcome) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="visitsTable" class="display table table-striped table-hover" style="width:100%; min-width: 900px;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('medical.visited_at') }}</th>
                                        <th>{{ __('medical.student') }}</th>
                                        <th>{{ __('medical.class') }}</th>
                                        <th>{{ __('medical.reason') }}</th>
                                        <th>{{ __('medical.outcome') }}</th>
                                        <th>{{ __('medical.recorded_by') }}</th>
                                        <th class="text-end">{{ __('medical.action') }}</th>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    const table = $('#visitsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('medical-records.index') }}",
            data: function (d) {
                d.outcome = $('#outcomeFilter').val();
                d.from = $('#fromFilter').val();
                d.to = $('#toFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'visited_at', name: 'visited_at' },
            { data: 'student_name', name: 'student.first_name' },
            { data: 'class_name', orderable: false, searchable: false },
            { data: 'reason', name: 'reason' },
            { data: 'outcome', name: 'outcome' },
            { data: 'recorded_by_name', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[1, 'desc']]
    });

    $('#outcomeFilter, #fromFilter, #toFilter').on('change', function () { table.ajax.reload(); });

    let searchTimer = null;
    $('#studentSearch').on('keyup', function () {
        const term = $(this).val();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            $.get("{{ route('medical-records.students.search') }}", { q: term }, function (rows) {
                const container = $('#studentResults').empty();
                if (!rows.length) {
                    container.append('<div class="text-muted p-2">@json(__('medical.no_students_found'))</div>');
                    return;
                }
                rows.forEach(function (row) {
                    container.append(
                        '<a href="' + row.url + '" class="list-group-item list-group-item-action d-flex justify-content-between">' +
                        '<span>' + row.name + ' <small class="text-muted">(' + (row.admission_number || '—') + ')</small></span>' +
                        '<span class="text-muted">' + (row.class || '') + '</span></a>'
                    );
                });
            });
        }, 300);
    });

    $(document).on('click', '.delete-visit-btn', function () {
        const url = $(this).data('url');
        Swal.fire({
            title: @json(__('medical.confirm_delete_visit')),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                success: function (resp) {
                    table.ajax.reload();
                    Swal.fire(@json(__('configuration.success')), resp.message, 'success');
                }
            });
        });
    });
});
</script>
@endsection
