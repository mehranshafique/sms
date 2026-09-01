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
    .att-overview .dash-stat.open-details {
        cursor: pointer;
    }
    .att-overview .class-row { cursor: pointer; }
    .att-overview .class-row:hover { background: rgba(91, 83, 232, .04); }
    [data-theme-version="dark"] .att-overview .class-row:hover { background: rgba(255, 255, 255, .04); }
</style>
@endsection

@section('content')
@php
    $students = $overview['students'];
    $staff = $overview['staff'];
    $classes = $overview['classes'];
    $todayLabel = \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y');
    $studentKpis = [
        ['bucket' => 'expected', 'value' => $students['expected'], 'label' => __('attendance.expected'), 'tint' => 'primary', 'icon' => 'la la-users', 'valueClass' => ''],
        ['bucket' => 'present', 'value' => $students['present'], 'label' => __('attendance.present'), 'tint' => 'success', 'icon' => 'la la-check-circle', 'valueClass' => 'text-tint-success'],
        ['bucket' => 'absent', 'value' => $students['absent'], 'label' => __('attendance.absent'), 'tint' => 'danger', 'icon' => 'la la-times-circle', 'valueClass' => 'text-tint-danger'],
        ['bucket' => 'late', 'value' => $students['late'], 'label' => __('attendance.late'), 'tint' => 'warning', 'icon' => 'la la-clock', 'valueClass' => 'text-tint-warning'],
        ['bucket' => 'not_checked_in', 'value' => $students['not_checked_in'], 'label' => __('attendance.not_checked_in'), 'tint' => 'info', 'icon' => 'la la-hourglass-half', 'valueClass' => 'text-tint-info'],
    ];
    $staffKpis = [
        ['bucket' => 'expected', 'value' => $staff['expected'], 'label' => __('attendance.expected'), 'tint' => 'primary', 'icon' => 'la la-user-tie', 'valueClass' => ''],
        ['bucket' => 'present', 'value' => $staff['present'], 'label' => __('attendance.present'), 'tint' => 'success', 'icon' => 'la la-check-circle', 'valueClass' => 'text-tint-success'],
        ['bucket' => 'absent', 'value' => $staff['absent'], 'label' => __('attendance.absent'), 'tint' => 'danger', 'icon' => 'la la-times-circle', 'valueClass' => 'text-tint-danger'],
        ['bucket' => 'late', 'value' => $staff['late'], 'label' => __('attendance.late'), 'tint' => 'warning', 'icon' => 'la la-clock', 'valueClass' => 'text-tint-warning'],
        ['bucket' => 'not_checked_in', 'value' => $staff['not_checked_in'], 'label' => __('attendance.not_checked_in'), 'tint' => 'info', 'icon' => 'la la-hourglass-half', 'valueClass' => 'text-tint-info'],
    ];
@endphp
<div class="content-body att-overview">
    <div class="container-fluid">

        {{-- Hero header (dashboard palette) --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="dash-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4" style="position:relative;z-index:1;">
                        <div>
                            <span class="dash-hero__chip mb-2"><i class="la la-calendar"></i> {{ $todayLabel }}</span>
                            <h3 class="text-white fw-bold mb-1">{{ __('attendance.overview_title') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('attendance.overview_subtitle') }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <form method="GET" action="{{ route('attendance.overview') }}" class="d-flex align-items-center gap-2">
                                <input type="date" id="overview_date" name="date" value="{{ $date }}" class="form-control form-control-sm" style="min-width:150px;" onchange="this.form.submit()">
                            </form>
                            <a href="{{ route('attendance.create') }}" class="btn btn-light btn-sm fw-bold">{{ __('attendance.mark_attendance') }}</a>
                            @if($canViewStaff)
                                <a href="{{ route('staff-attendance.create') }}" class="btn btn-outline-light btn-sm">{{ __('staff.mark_staff_attendance') ?? __('attendance.staff_attendance_title') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Students --}}
        <div class="dash-panel mb-3">
            <div class="dash-panel__head">
                <h4 class="dash-panel__title">{{ __('attendance.overview_students') }}</h4>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('attendance.analytics.index') }}" class="text-tint-primary small">{{ __('attendance.analytics_title') }}</a>
                    <span class="badge rounded-pill" style="background:rgba(91,83,232,.12);color:var(--dash-primary);">
                        {{ __('attendance.attendance_rate') }}: {{ $students['rate'] }}%
                    </span>
                </div>
            </div>
            <div class="dash-panel__body pt-0">
                <div class="row g-3">
                    @foreach($studentKpis as $kpi)
                    <div class="col-xl col-md-4 col-6">
                        <div class="dash-stat open-details"
                             data-audience="students"
                             data-bucket="{{ $kpi['bucket'] }}"
                             role="button"
                             tabindex="0">
                            <div class="d-flex align-items-center" style="gap:14px;">
                                <span class="dash-stat__icon tint-{{ $kpi['tint'] }}"><i class="{{ $kpi['icon'] }}"></i></span>
                                <div>
                                    <p class="dash-stat__label">{{ $kpi['label'] }}</p>
                                    <h4 class="dash-stat__value {{ $kpi['valueClass'] }}">{{ $kpi['value'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($canViewStaff)
        {{-- Staff --}}
        <div class="dash-panel mb-3">
            <div class="dash-panel__head">
                <h4 class="dash-panel__title">{{ __('attendance.overview_staff') }}</h4>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('staff-attendance.analytics') }}" class="text-tint-primary small">{{ __('attendance.staff_analytics_title') }}</a>
                    <span class="badge rounded-pill" style="background:rgba(43,182,115,.12);color:var(--dash-success);">
                        {{ __('attendance.attendance_rate') }}: {{ $staff['rate'] }}%
                    </span>
                </div>
            </div>
            <div class="dash-panel__body pt-0">
                <div class="row g-3">
                    @foreach($staffKpis as $kpi)
                    <div class="col-xl col-md-4 col-6">
                        <div class="dash-stat open-details"
                             data-audience="staff"
                             data-bucket="{{ $kpi['bucket'] }}"
                             role="button"
                             tabindex="0">
                            <div class="d-flex align-items-center" style="gap:14px;">
                                <span class="dash-stat__icon tint-{{ $kpi['tint'] }}"><i class="{{ $kpi['icon'] }}"></i></span>
                                <div>
                                    <p class="dash-stat__label">{{ $kpi['label'] }}</p>
                                    <h4 class="dash-stat__value {{ $kpi['valueClass'] }}">{{ $kpi['value'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Students by class --}}
        <div class="dash-panel">
            <div class="dash-panel__head">
                <div>
                    <h4 class="dash-panel__title mb-0">{{ __('attendance.students_by_class') }}</h4>
                    <span class="dash-mini-label">{{ __('attendance.total_enrollment_label', ['count' => $overview['total_enrollment']]) }}</span>
                </div>
                <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary">{{ __('attendance.view_all_students') }}</a>
            </div>
            <div class="dash-panel__body pt-0">
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
                                <td class="text-center text-tint-success" data-order="{{ $class['present'] }}">{{ $class['present'] }}</td>
                                <td class="text-center text-tint-warning" data-order="{{ $class['late'] }}">{{ $class['late'] }}</td>
                                <td class="text-center text-tint-danger" data-order="{{ $class['absent'] }}">{{ $class['absent'] }}</td>
                                <td class="text-center text-tint-info" data-order="{{ $class['not_checked_in'] }}">{{ $class['not_checked_in'] }}</td>
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
