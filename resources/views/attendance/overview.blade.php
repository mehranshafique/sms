@extends('layout.layout')

@section('styles')
@include('dashboard.partials.dashboard-styles')
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
    .att-overview .dataTables_wrapper .dataTables_filter input {
        border: 1px solid var(--dash-border, #d9dee3);
        border-radius: 0.375rem;
        padding: 0.4375rem 0.875rem;
        margin-left: 0.5em;
        outline: none;
    }
    .att-overview .kpi-card {
        cursor: pointer;
        border: 1px solid var(--dash-border, #eef1f6);
        border-radius: 14px;
        background: #fff;
        transition: transform .15s ease, box-shadow .15s ease;
        height: 100%;
    }
    .att-overview .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(31, 37, 51, .08);
    }
    .att-overview .kpi-card .kpi-value { font-size: 1.75rem; font-weight: 700; margin: 0; color: var(--dash-ink, #1f2533); }
    .att-overview .kpi-card .kpi-label { font-size: .8rem; color: var(--dash-muted, #8a93a6); margin: 0; text-transform: uppercase; letter-spacing: .02em; }
    .att-overview .kpi-card .kpi-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem;
    }
    .att-overview .tint-primary { background: rgba(91,83,232,.12); color: var(--dash-primary, #5b53e8); }
    .att-overview .tint-success { background: rgba(43,182,115,.12); color: var(--dash-success, #2bb673); }
    .att-overview .tint-danger { background: rgba(239,86,117,.12); color: var(--dash-danger, #ef5675); }
    .att-overview .tint-warning { background: rgba(245,166,35,.12); color: var(--dash-warning, #f5a623); }
    .att-overview .tint-info { background: rgba(42,169,224,.12); color: var(--dash-info, #2aa9e0); }
    .att-overview .class-row { cursor: pointer; }
    .att-overview .class-row:hover { background: rgba(91,83,232,.04); }
    .att-overview .section-title { font-size: 1.05rem; font-weight: 700; color: var(--dash-ink, #1f2533); }
    [data-theme-version="dark"] .att-overview .kpi-card {
        background: var(--digitex-card-bg, #1e2746);
        border-color: var(--digitex-card-border, rgba(255,255,255,.08));
    }
    [data-theme-version="dark"] .att-overview .kpi-card .kpi-value,
    [data-theme-version="dark"] .att-overview .section-title { color: var(--digitex-text, #e8ebf5); }
    [data-theme-version="dark"] .att-overview .kpi-card .kpi-label { color: var(--digitex-muted, #9ca3af); }
    [data-theme-version="dark"] .att-overview .class-row:hover { background: rgba(255,255,255,.04); }
</style>
@endsection

@section('content')
@php
    $students = $overview['students'];
    $staff = $overview['staff'];
    $classes = $overview['classes'];
@endphp
<div class="content-body att-overview">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-3 align-items-center">
            <div class="col-md-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('attendance.overview_title') }}</h4>
                    <p class="mb-0">{{ __('attendance.overview_subtitle') }}</p>
                </div>
            </div>
            <div class="col-md-6 p-md-0 d-flex justify-content-md-end align-items-center gap-2 flex-wrap mt-2 mt-md-0">
                <form method="GET" action="{{ route('attendance.overview') }}" class="d-flex align-items-center gap-2">
                    <label class="mb-0 small text-muted" for="overview_date">{{ __('attendance.date') }}</label>
                    <input type="date" id="overview_date" name="date" value="{{ $date }}" class="form-control form-control-sm" style="min-width:150px;" onchange="this.form.submit()">
                </form>
                <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-sm">{{ __('attendance.mark_attendance') }}</a>
                @if($canViewStaff)
                    <a href="{{ route('staff-attendance.create') }}" class="btn btn-outline-primary btn-sm">{{ __('staff.mark_staff_attendance') ?? __('attendance.staff_attendance_title') }}</a>
                @endif
            </div>
        </div>

        {{-- Students KPIs --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="section-title mb-0">{{ __('attendance.overview_students') }}</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('attendance.analytics.index') }}" class="small">{{ __('attendance.analytics_title') }}</a>
                <span class="badge light badge-primary">{{ __('attendance.attendance_rate') }}: {{ $students['rate'] }}%</span>
            </div>
        </div>
        <div class="row g-3 mb-4">
            @foreach([
                ['bucket' => 'expected', 'value' => $students['expected'], 'label' => __('attendance.expected'), 'tint' => 'primary', 'icon' => 'la la-users'],
                ['bucket' => 'present', 'value' => $students['present'], 'label' => __('attendance.present'), 'tint' => 'success', 'icon' => 'la la-check-circle'],
                ['bucket' => 'absent', 'value' => $students['absent'], 'label' => __('attendance.absent'), 'tint' => 'danger', 'icon' => 'la la-times-circle'],
                ['bucket' => 'late', 'value' => $students['late'], 'label' => __('attendance.late'), 'tint' => 'warning', 'icon' => 'la la-clock'],
                ['bucket' => 'not_checked_in', 'value' => $students['not_checked_in'], 'label' => __('attendance.not_checked_in'), 'tint' => 'info', 'icon' => 'la la-hourglass-half'],
            ] as $kpi)
            <div class="col-xl col-md-4 col-6">
                <div class="kpi-card p-3 open-details" data-audience="students" data-bucket="{{ $kpi['bucket'] }}" role="button" tabindex="0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="kpi-label">{{ $kpi['label'] }}</p>
                            <p class="kpi-value">{{ $kpi['value'] }}</p>
                        </div>
                        <span class="kpi-icon tint-{{ $kpi['tint'] }}"><i class="{{ $kpi['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($canViewStaff)
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="section-title mb-0">{{ __('attendance.overview_staff') }}</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('staff-attendance.analytics') }}" class="small">{{ __('attendance.staff_analytics_title') }}</a>
                <span class="badge light badge-primary">{{ __('attendance.attendance_rate') }}: {{ $staff['rate'] }}%</span>
            </div>
        </div>
        <div class="row g-3 mb-4">
            @foreach([
                ['bucket' => 'expected', 'value' => $staff['expected'], 'label' => __('attendance.expected'), 'tint' => 'primary', 'icon' => 'la la-user-tie'],
                ['bucket' => 'present', 'value' => $staff['present'], 'label' => __('attendance.present'), 'tint' => 'success', 'icon' => 'la la-check-circle'],
                ['bucket' => 'absent', 'value' => $staff['absent'], 'label' => __('attendance.absent'), 'tint' => 'danger', 'icon' => 'la la-times-circle'],
                ['bucket' => 'late', 'value' => $staff['late'], 'label' => __('attendance.late'), 'tint' => 'warning', 'icon' => 'la la-clock'],
                ['bucket' => 'not_checked_in', 'value' => $staff['not_checked_in'], 'label' => __('attendance.not_checked_in'), 'tint' => 'info', 'icon' => 'la la-hourglass-half'],
            ] as $kpi)
            <div class="col-xl col-md-4 col-6">
                <div class="kpi-card p-3 open-details" data-audience="staff" data-bucket="{{ $kpi['bucket'] }}" role="button" tabindex="0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="kpi-label">{{ $kpi['label'] }}</p>
                            <p class="kpi-value">{{ $kpi['value'] }}</p>
                        </div>
                        <span class="kpi-icon tint-{{ $kpi['tint'] }}"><i class="{{ $kpi['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Students by class --}}
        <div class="card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="card-title mb-0">{{ __('attendance.students_by_class') }}</h4>
                    <small class="text-muted">{{ __('attendance.total_enrollment_label', ['count' => $overview['total_enrollment']]) }}</small>
                </div>
                <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary">{{ __('attendance.view_all_students') }}</a>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive digitex-dt-wrap">
                    <table id="classesOverviewTable" class="table table-hover align-middle mb-0 display" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('attendance.class') }}</th>
                                <th class="text-center">{{ __('attendance.enrollment') }}</th>
                                <th class="text-center">{{ __('attendance.present') }}</th>
                                <th class="text-center">{{ __('attendance.late') }}</th>
                                <th class="text-center">{{ __('attendance.absent') }}</th>
                                <th class="text-center">{{ __('attendance.not_checked_in') }}</th>
                                <th class="text-center">{{ __('attendance.attendance_rate') }}</th>
                                <th class="text-end">{{ __('attendance.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                            <tr class="class-row" data-class-id="{{ $class['class_section_id'] }}">
                                <td class="fw-bold">{{ $class['label'] }}</td>
                                <td class="text-center" data-order="{{ $class['enrollment'] }}">{{ $class['enrollment'] }}</td>
                                <td class="text-center text-success" data-order="{{ $class['present'] }}">{{ $class['present'] }}</td>
                                <td class="text-center text-warning" data-order="{{ $class['late'] }}">{{ $class['late'] }}</td>
                                <td class="text-center text-danger" data-order="{{ $class['absent'] }}">{{ $class['absent'] }}</td>
                                <td class="text-center text-info" data-order="{{ $class['not_checked_in'] }}">{{ $class['not_checked_in'] }}</td>
                                <td class="text-center" data-order="{{ $class['rate'] }}">{{ $class['rate'] }}%</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-xs btn-primary sharp open-details me-1"
                                        data-audience="students" data-bucket="expected"
                                        data-class-section-id="{{ $class['class_section_id'] }}"
                                        title="{{ __('attendance.view_details') }}">
                                        <i class="fa fa-list"></i>
                                    </button>
                                    <a href="{{ route('students.index', ['class_section_id' => $class['class_section_id']]) }}"
                                       class="btn btn-xs btn-info sharp"
                                       title="{{ __('attendance.view_class_roster') }}">
                                        <i class="fa fa-users"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if(empty($classes))
                        <p class="text-center text-muted py-4 mb-0">{{ __('attendance.no_classes_enrolled') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Details modal --}}
<div class="modal fade" id="attDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attDetailsTitle">{{ __('attendance.view_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="search" id="attDetailsSearch" class="form-control mb-3" placeholder="{{ __('attendance.search_placeholder') }}">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('attendance.student_name') }}</th>
                                <th>{{ __('attendance.class') }}</th>
                                <th>{{ __('attendance.status') }}</th>
                                <th>{{ __('attendance.check_in') ?? 'Check-in' }}</th>
                            </tr>
                        </thead>
                        <tbody id="attDetailsBody">
                            <tr><td colspan="4" class="text-center text-muted py-3">{{ __('pagination.processing') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    const detailsUrl = @json(route('attendance.overview.details'));
    const overviewDate = @json($date);
    const labels = {
        students: @json(__('attendance.overview_students')),
        staff: @json(__('attendance.overview_staff')),
        expected: @json(__('attendance.expected')),
        present: @json(__('attendance.present')),
        absent: @json(__('attendance.absent')),
        late: @json(__('attendance.late')),
        not_checked_in: @json(__('attendance.not_checked_in')),
        empty: @json(__('attendance.no_records_found')),
        error: @json(__('attendance.error_occurred')),
    };

    let detailRows = [];
    const modalEl = document.getElementById('attDetailsModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const body = document.getElementById('attDetailsBody');
    const title = document.getElementById('attDetailsTitle');
    const search = document.getElementById('attDetailsSearch');

    function statusBadge(status, label) {
        const map = {
            present: 'badge-success',
            half_day: 'badge-primary',
            late: 'badge-warning',
            absent: 'badge-danger',
            excused: 'badge-info',
            not_checked_in: 'badge-secondary',
        };
        const cls = map[status] || 'badge-secondary';
        return `<span class="badge ${cls}">${label || status}</span>`;
    }

    function renderRows(rows) {
        if (!body) return;
        if (!rows.length) {
            body.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">${labels.empty}</td></tr>`;
            return;
        }
        body.innerHTML = rows.map(row => {
            const name = row.url
                ? `<a href="${row.url}" class="fw-bold text-primary">${row.name}</a>`
                : `<span class="fw-bold">${row.name}</span>`;
            const secondary = row.secondary ? `<div class="small text-muted">${row.secondary}</div>` : '';
            return `<tr>
                <td>${name}${secondary}</td>
                <td>${row.meta || '—'}</td>
                <td>${statusBadge(row.status, row.status_label)}</td>
                <td>${row.check_in_label || '—'}</td>
            </tr>`;
        }).join('');
    }

    function openDetails(audience, bucket, classSectionId) {
        if (!modal) return;
        const bucketLabel = labels[bucket] || bucket;
        const audienceLabel = labels[audience] || audience;
        title.textContent = `${audienceLabel} — ${bucketLabel}`;
        body.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">{{ __('pagination.processing') }}</td></tr>`;
        if (search) search.value = '';
        modal.show();

        const params = new URLSearchParams({
            audience: audience,
            bucket: bucket,
            date: overviewDate,
        });
        if (classSectionId) params.set('class_section_id', classSectionId);

        fetch(detailsUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) throw data;
                return data;
            })
            .then((data) => {
                detailRows = data.rows || [];
                renderRows(detailRows);
            })
            .catch(() => {
                body.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">${labels.error}</td></tr>`;
            });
    }

    document.addEventListener('click', function (e) {
        const detailBtn = e.target.closest('.open-details');
        if (detailBtn) {
            e.stopPropagation();
            openDetails(
                detailBtn.dataset.audience || 'students',
                detailBtn.dataset.bucket || 'expected',
                detailBtn.dataset.classSectionId || null
            );
            return;
        }

        const classRow = e.target.closest('#classesOverviewTable tbody tr.class-row');
        if (classRow && !e.target.closest('a, button')) {
            openDetails('students', 'expected', classRow.dataset.classId);
        }
    });

    document.addEventListener('keydown', function (e) {
        const detailBtn = e.target.closest('.open-details');
        if (detailBtn && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            detailBtn.click();
        }
    });

    const classesTableEl = document.getElementById('classesOverviewTable');
    if (classesTableEl && classesTableEl.querySelector('tbody tr')) {
        $('#classesOverviewTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            columnDefs: [
                { targets: [1, 2, 3, 4, 5, 6], className: 'text-center' },
                { targets: 7, orderable: false, searchable: false, className: 'text-end' },
            ],
            language: {
                search: @json(__('pagination.search')),
                lengthMenu: @json(__('pagination.show')) + ' _MENU_ ' + @json(__('pagination.entries')),
                info: @json(__('pagination.info')),
                infoEmpty: @json(__('pagination.info_empty')),
                infoFiltered: @json(__('pagination.info_filtered')),
                zeroRecords: @json(__('pagination.zero_records')),
                emptyTable: @json(__('attendance.no_classes_enrolled')),
            },
        });
    }

    if (search) {
        search.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            if (!q) {
                renderRows(detailRows);
                return;
            }
            renderRows(detailRows.filter((r) => {
                return (r.name || '').toLowerCase().includes(q)
                    || (r.secondary || '').toLowerCase().includes(q)
                    || (r.meta || '').toLowerCase().includes(q)
                    || (r.status_label || '').toLowerCase().includes(q);
            }));
        });
    }
})();
</script>
@endsection
