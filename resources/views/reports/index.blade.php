@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('reports.page_title') }}</h4>
                    <p class="mb-0">{{ __('reports.subtitle') ?? 'Generate Student Bulletins & Transcripts' }}</p>
                </div>
            </div>
        </div>

        @if(has_ai_access())
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-light border mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-color:#ddd6fe !important;background:#faf5ff;">
                    <div>
                        <strong><i class="la la-magic text-primary"></i> {{ __('ai.tools.bulk_report_comments') }}</strong>
                        <div class="small text-muted">{{ __('ai.tools.bulk_report_comments_desc') }}</div>
                    </div>
                    <a href="{{ route('results.index') }}" class="btn btn-primary btn-sm"><i class="la la-magic me-1"></i> {{ __('ai.btn_bulk_comments') }}</a>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            {{-- 1. Student Bulletin Card --}}
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pb-0">
                        <h4 class="card-title text-primary">{{ __('reports.student_bulletin') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4 fs-13">{{ __('reports.bulletin_description') ?? 'Generate reports for a specific Period, Trimester, or Semester.' }}</p>
                        
                        <form action="{{ route('reports.bulletin') }}" method="GET" id="bulletinForm">
                            
                            {{-- Mode Toggle --}}
                            <div class="mb-3">
                                <label class="form-label d-block fw-bold mb-2">{{ __('reports.generation_mode') }}</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="mode" id="modeSingle" value="single" checked>
                                    <label class="btn btn-outline-primary" for="modeSingle"><i class="fa fa-user me-1"></i> {{ __('reports.single_student') }}</label>

                                    <input type="radio" class="btn-check" name="mode" id="modeBulk" value="bulk">
                                    <label class="btn btn-outline-primary" for="modeBulk"><i class="fa fa-users me-1"></i> {{ __('reports.whole_class') }}</label>
                                </div>
                            </div>

                            {{-- Single: Student Select --}}
                            <div class="mb-3" id="studentInputGroup">
                                <label class="form-label">{{ __('reports.select_student') }} <span class="text-danger">*</span></label>
                                <select class="form-control default-select" name="student_id" id="studentSelect">
                                    <option value="">{{ __('reports.select_student') }}</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" data-cycle="{{ $student->education_cycle ?? 'primary' }}">{{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Bulk: Class Select --}}
                            <div class="mb-3 d-none" id="classInputGroup">
                                <label class="form-label">{{ __('class_section.page_title') }} <span class="text-danger">*</span></label>
                                <select class="form-control default-select" name="class_section_id" id="classSelect">
                                    <option value="">{{ __('invoice.select_class') }}</option>
                                    @foreach($classes as $cls)
                                        @php $cycle = $cls->gradeLevel->education_cycle ?? 'primary'; $cycleVal = is_object($cycle) ? $cycle->value : $cycle; @endphp
                                        <option value="{{ $cls->id }}" data-cycle="{{ $cycleVal }}">{{ $cls->gradeLevel->name }} - {{ $cls->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Report Scope --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('reports.report_scope') ?? 'Report Scope' }} <span class="text-danger">*</span></label>
                                <select class="form-control default-select" name="report_scope" id="reportScope" required>
                                    <option value="">-- {{ __('reports.select_scope') ?? 'Select Scope' }} --</option>
                                    <option value="period" data-scope="period">{{ __('reports.period_report') ?? 'Period Report' }}</option>
                                    <option value="trimester" data-scope="trimester">{{ __('reports.trimester_report') ?? 'Trimester Report' }}</option>
                                    <option value="semester" data-scope="semester">{{ __('reports.semester_report') ?? 'Semester Report' }}</option>
                                    <option value="session" data-scope="session">{{ __('reports.session_report') }}</option>
                                </select>
                                <small class="text-muted d-block mt-1" id="scopeCycleHint">{{ __('reports.scope_cycle_hint') }}</small>
                                <input type="hidden" name="type" id="typeInput">
                            </div>

                            {{-- Dynamic Fields --}}
                            <div class="row">
                                <div class="col-md-12 mb-3 d-none" id="periodGroup">
                                    <label class="form-label">{{ __('reports.select_period') ?? 'Select Period' }} <span class="text-danger">*</span></label>
                                    <select class="form-control default-select" name="period" id="periodSelect">
                                        <option value="">-- {{ __('reports.select_period') }} --</option>
                                        <option value="p1">{{ __('reports.period') }} 1</option>
                                        <option value="p2">{{ __('reports.period') }} 2</option>
                                        <option value="p3">{{ __('reports.period') }} 3</option>
                                        <option value="p4">{{ __('reports.period') }} 4</option>
                                        <option value="p5" class="period-primary-only">{{ __('reports.period') }} 5</option>
                                        <option value="p6" class="period-primary-only">{{ __('reports.period') }} 6</option>
                                        <option value="trimester_exam_1">{{ __('reports.exam_stage_trimester', ['n' => 1]) }}</option>
                                        <option value="trimester_exam_2">{{ __('reports.exam_stage_trimester', ['n' => 2]) }}</option>
                                        <option value="trimester_exam_3">{{ __('reports.exam_stage_trimester', ['n' => 3]) }}</option>
                                        <option value="semester_exam_1">{{ __('reports.exam_stage_semester', ['n' => 1]) }}</option>
                                        <option value="semester_exam_2">{{ __('reports.exam_stage_semester', ['n' => 2]) }}</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3 d-none" id="trimesterGroup">
                                    <label class="form-label">{{ __('reports.select_trimester') ?? 'Select Trimester' }} <span class="text-danger">*</span></label>
                                    <select class="form-control default-select" name="trimester" id="trimesterSelect">
                                        <option value="">-- {{ __('reports.select_trimester') }} --</option>
                                        <option value="1">{{ __('reports.trimester') }} 1</option>
                                        <option value="2">{{ __('reports.trimester') }} 2</option>
                                        <option value="3">{{ __('reports.trimester') }} 3</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3 d-none" id="semesterGroup">
                                    <label class="form-label">{{ __('reports.select_semester') ?? 'Select Semester' }} <span class="text-danger">*</span></label>
                                    <select class="form-control default-select" name="semester" id="semesterSelect">
                                        <option value="">-- {{ __('reports.select_semester') }} --</option>
                                        <option value="1">{{ __('reports.semester') }} 1</option>
                                        <option value="2">{{ __('reports.semester') }} 2</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-2 shadow-sm" id="btnBulletin">
                                <i class="fa fa-file-pdf-o me-2"></i> {{ __('reports.generate_bulletin') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if(isset($institutionType) && in_array($institutionType, ['university', 'mixed']))
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pb-0">
                        <h4 class="card-title text-secondary">{{ __('reports.transcript') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4 fs-13">{{ __('reports.transcript_description') ?? 'Generate a comprehensive academic history report (Cumulative).' }}</p>
                        
                        <form action="{{ route('reports.transcript') }}" method="GET" id="transcriptForm">
                            <div class="mb-3">
                                <label class="form-label">{{ __('reports.select_student') }}</label>
                                <select class="form-control default-select" name="student_id" required>
                                    <option value="">{{ __('reports.select_student') }}</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-secondary w-100 mt-5 shadow-sm" id="btnTranscript">
                                <i class="fa fa-history me-2"></i> {{ __('reports.generate_transcript') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @can('academic_report.view')
        <div class="row mt-2">
            <div class="col-12">
                <div class="alert alert-light border mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <strong>{{ __('state_exam.heading') }}</strong>
                        <div class="small text-muted">{{ __('state_exam.subtitle') }}</div>
                    </div>
                    <a href="{{ route('state-exams.index') }}" class="btn btn-outline-primary btn-sm">{{ __('state_exam.page_title') }}</a>
                </div>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const studentGroup = document.getElementById('studentInputGroup');
        const classGroup = document.getElementById('classInputGroup');
        const studentSelect = document.getElementById('studentSelect');
        const classSelect = document.getElementById('classSelect');
        const reportScope = document.getElementById('reportScope');
        const periodSelect = document.getElementById('periodSelect');
        const trimesterSelect = document.getElementById('trimesterSelect');
        const semesterSelect = document.getElementById('semesterSelect');
        const scopeCycleHint = document.getElementById('scopeCycleHint');
        const scopeOptionsUrl = @json(route('reports.scope_options'));
        const noRecordsMsg = @json(__('reports.no_records_found'));
        const noStudentsMsg = @json(__('reports.no_students_in_class'));
        const noEnrollmentMsg = @json(__('reports.no_enrollment'));
        const sessionNeedStudentMsg = @json(__('reports.session_need_student'));
        const invalidPeriodMsg = @json(__('reports.error_invalid_period_for_cycle'));
        const defaultScopeHint = @json(__('reports.scope_cycle_hint'));
        const noticeTitle = @json(__('reports.notice_title'));
        const noResultsTitle = @json(__('reports.no_results_title'));
        const infoMessages = [noRecordsMsg, noStudentsMsg, noEnrollmentMsg, sessionNeedStudentMsg, invalidPeriodMsg];
        let latestOfficialStage = null;
        let lastScopePayload = null;

        const scopesByCycle = {
            primary: ['period', 'trimester'],
            secondary: ['period', 'semester'],
            vocational: ['period', 'semester'],
            university: ['session'],
            lmd: ['session'],
        };
        const periodsByCycle = {
            primary: ['p1', 'p2', 'p3', 'p4', 'p5', 'p6'],
            secondary: ['p1', 'p2', 'p3', 'p4'],
            vocational: ['p1', 'p2', 'p3', 'p4'],
            university: [],
            lmd: [],
        };

        const reportScopeOptionsHtml = reportScope ? reportScope.innerHTML : '';
        const periodOptionsHtml = periodSelect ? periodSelect.innerHTML : '';

        function hasSelectpicker() {
            return typeof $ !== 'undefined' && $.fn.selectpicker;
        }

        function selectIsHidden(selectEl) {
            return !selectEl || !!selectEl.closest('.d-none, [hidden]');
        }

        function refreshPicker(selectEl, forceLiveSearch) {
            if (!selectEl || !hasSelectpicker() || selectIsHidden(selectEl) || selectEl.disabled) {
                return;
            }
            const $el = $(selectEl);
            const liveSearch = forceLiveSearch === true || (forceLiveSearch !== false && selectEl.options.length > 12);
            try {
                if ($el.data('selectpicker')) {
                    $el.selectpicker('destroy');
                }
            } catch (e) {}
            try {
                $el.selectpicker({
                    liveSearch: liveSearch,
                    size: 10,
                    container: 'body',
                    dropupAuto: false,
                    hideDisabled: false,
                });
            } catch (e2) {}
        }

        function pickerVal(selectEl) {
            if (!selectEl || selectEl.disabled) {
                return '';
            }
            if (hasSelectpicker()) {
                const $el = $(selectEl);
                if ($el.data('selectpicker')) {
                    const v = $el.selectpicker('val');
                    return v == null ? '' : String(v);
                }
            }
            return selectEl.value || '';
        }

        function setPickerVal(selectEl, value) {
            if (!selectEl) {
                return;
            }
            selectEl.value = value || '';
            if (hasSelectpicker()) {
                const $el = $(selectEl);
                if ($el.data('selectpicker')) {
                    $el.selectpicker('val', value || '');
                }
            }
        }

        var suppressEntityChange = false;

        function deactivateSelect(selectEl) {
            if (!selectEl) {
                return;
            }
            suppressEntityChange = true;
            try {
                selectEl.value = '';
                if (hasSelectpicker() && $(selectEl).data('selectpicker')) {
                    try { $(selectEl).selectpicker('val', ''); } catch (e) {}
                }
                selectEl.removeAttribute('name');
                selectEl.required = false;
                selectEl.disabled = true;
            } finally {
                suppressEntityChange = false;
            }
        }

        function activateSelect(selectEl, nameAttr) {
            if (!selectEl) {
                return;
            }
            selectEl.disabled = false;
            selectEl.setAttribute('name', nameAttr);
            selectEl.required = true;
            refreshPicker(selectEl);
        }

        function rebuildOptions(selectEl, originalHtml, allowedValues) {
            if (!selectEl || !originalHtml) {
                return;
            }
            const keepValue = selectEl.value;
            const temp = document.createElement('select');
            temp.innerHTML = originalHtml;

            selectEl.innerHTML = '';
            Array.from(temp.options).forEach(function (opt) {
                if (!opt.value || !allowedValues || allowedValues.indexOf(opt.value) !== -1) {
                    selectEl.appendChild(opt.cloneNode(true));
                }
            });

            if (keepValue && allowedValues && allowedValues.indexOf(keepValue) !== -1) {
                selectEl.value = keepValue;
            } else if (keepValue && !allowedValues) {
                selectEl.value = keepValue;
            } else {
                selectEl.value = '';
            }
        }

        function showTermGroup(groupId, selectEl, typeValue) {
            const group = document.getElementById(groupId);
            if (!group || !selectEl) {
                return;
            }
            group.classList.remove('d-none');
            selectEl.disabled = false;
            selectEl.required = true;
            document.getElementById('typeInput').value = typeValue;
            requestAnimationFrame(function () {
                refreshPicker(selectEl, false);
            });
        }

        function hideTermGroups(clearValues) {
            document.getElementById('periodGroup').classList.add('d-none');
            document.getElementById('trimesterGroup').classList.add('d-none');
            document.getElementById('semesterGroup').classList.add('d-none');
            periodSelect.required = false;
            trimesterSelect.required = false;
            semesterSelect.required = false;
            if (clearValues !== false) {
                setPickerVal(periodSelect, '');
                setPickerVal(trimesterSelect, '');
                setPickerVal(semesterSelect, '');
            }
            document.getElementById('typeInput').value = 'term';
        }

        function resetReportScope() {
            if (!reportScope) {
                return;
            }
            rebuildOptions(reportScope, reportScopeOptionsHtml, null);
            rebuildOptions(periodSelect, periodOptionsHtml, null);
            setPickerVal(reportScope, '');
            hideTermGroups(true);
            if (scopeCycleHint) {
                scopeCycleHint.textContent = defaultScopeHint;
            }
            refreshPicker(reportScope);
            refreshPicker(periodSelect);
            refreshPicker(trimesterSelect);
            refreshPicker(semesterSelect);
        }

        function applyScopeOptions(payload) {
            if (!payload || !reportScope) {
                return;
            }
            lastScopePayload = payload;
            latestOfficialStage = payload.latest_official || null;
            const allowed = payload.scopes || [];
            const previousScope = pickerVal(reportScope) || reportScope.value;

            rebuildOptions(reportScope, reportScopeOptionsHtml, allowed);

            if (payload.period_stages && payload.period_stages.length) {
                const keep = periodSelect.value;
                periodSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '-- ' + @json(__('reports.select_period')) + ' --';
                periodSelect.appendChild(placeholder);
                payload.period_stages.forEach(function (stage) {
                    const opt = document.createElement('option');
                    opt.value = stage.key;
                    opt.textContent = stage.selectable
                        ? (stage.status === 'reopened'
                            ? @json(__('reports.reopened_option')).replace(':label', stage.label)
                            : stage.label)
                        : (stage.label + ' — ' + @json(__('reports.stage_not_available')).split('.')[0]);
                    periodSelect.appendChild(opt);
                });
                if (keep && Array.from(periodSelect.options).some(function (o) { return o.value === keep && !o.disabled; })) {
                    periodSelect.value = keep;
                }
            } else {
                const allowedPeriods = payload.periods || [];
                rebuildOptions(
                    periodSelect,
                    periodOptionsHtml,
                    allowedPeriods.length ? allowedPeriods : null
                );
            }

            if (payload.term_stages && payload.term_stages.length) {
                const isTrimester = (payload.cycle === 'primary');
                const termSelect = isTrimester ? trimesterSelect : semesterSelect;
                if (termSelect) {
                    Array.from(termSelect.options).forEach(function (opt) {
                        if (!opt.value) return;
                        const meta = payload.term_stages.find(function (t) { return String(t.number) === String(opt.value); });
                        if (meta) {
                            opt.disabled = !meta.selectable;
                            opt.textContent = meta.selectable
                                ? (meta.reopened
                                    ? @json(__('reports.reopened_option')).replace(':label', meta.label)
                                    : meta.label)
                                : (meta.label + ' — ' + @json(__('reports.stage_not_available')).split('.')[0]);
                        }
                    });
                }
            }

            if (previousScope && allowed.indexOf(previousScope) === -1) {
                setPickerVal(reportScope, '');
                hideTermGroups(true);
            } else {
                setPickerVal(reportScope, previousScope || '');
                if (previousScope === 'period' || previousScope === 'trimester' || previousScope === 'semester') {
                    applyVisibleTermGroup(previousScope);
                    return;
                }
            }

            refreshPicker(reportScope, true);
        }

        function applyVisibleTermGroup(scope) {
            hideTermGroups(false);
            if (scope === 'period') {
                showTermGroup('periodGroup', periodSelect, 'period');
            } else if (scope === 'trimester') {
                showTermGroup('trimesterGroup', trimesterSelect, 'term');
            } else if (scope === 'semester') {
                showTermGroup('semesterGroup', semesterSelect, 'term');
            }
        }

        function applyCycleLocally(cycle) {
            if (!cycle) {
                resetReportScope();
                return;
            }
            applyScopeOptions({
                scopes: scopesByCycle[cycle] || ['period', 'trimester', 'semester'],
                periods: periodsByCycle[cycle] || ['p1', 'p2', 'p3', 'p4', 'p5', 'p6'],
            });
        }

        function isBulkMode() {
            return document.getElementById('modeBulk').checked;
        }

        function getSelectedCycle() {
            const select = isBulkMode() ? classSelect : studentSelect;
            const value = pickerVal(select) || (select ? select.value : '');
            if (!select || !value) {
                return null;
            }
            const option = Array.from(select.options).find(function (opt) { return opt.value === value; });
            return option ? (option.getAttribute('data-cycle') || null) : null;
        }

        function repairVisiblePickers() {
            const bulk = isBulkMode();
            if (bulk) {
                classGroup.classList.remove('d-none');
                studentGroup.classList.add('d-none');
                if (classSelect && !classSelect.disabled) {
                    refreshPicker(classSelect);
                }
            } else {
                studentGroup.classList.remove('d-none');
                classGroup.classList.add('d-none');
                if (studentSelect && !studentSelect.disabled) {
                    refreshPicker(studentSelect);
                }
            }
            if (reportScope && !reportScope.disabled) {
                refreshPicker(reportScope);
            }
            if (!document.getElementById('periodGroup').classList.contains('d-none')) {
                refreshPicker(periodSelect);
            }
            if (!document.getElementById('trimesterGroup').classList.contains('d-none')) {
                refreshPicker(trimesterSelect);
            }
            if (!document.getElementById('semesterGroup').classList.contains('d-none')) {
                refreshPicker(semesterSelect);
            }
        }

        function toggleMode() {
            const bulk = isBulkMode();

            if (bulk) {
                studentGroup.classList.add('d-none');
                classGroup.classList.remove('d-none');
                deactivateSelect(studentSelect);
                activateSelect(classSelect, 'class_section_id');
            } else {
                classGroup.classList.add('d-none');
                studentGroup.classList.remove('d-none');
                deactivateSelect(classSelect);
                activateSelect(studentSelect, 'student_id');
            }

            resetReportScope();
            // Defer picker repair so bootstrap-select measures visible width correctly
            requestAnimationFrame(function () {
                repairVisiblePickers();
            });
        }

        let scopeRequestSeq = 0;
        async function refreshCycleScopes() {
            const bulk = isBulkMode();
            const select = bulk ? classSelect : studentSelect;
            const seq = ++scopeRequestSeq;

            const selectedId = pickerVal(select) || select.value;
            if (!select || !selectedId || select.disabled) {
                resetReportScope();
                repairVisiblePickers();
                return;
            }

            applyCycleLocally(getSelectedCycle());

            const params = new URLSearchParams();
            if (bulk) {
                params.set('class_section_id', selectedId);
            } else {
                params.set('student_id', selectedId);
            }

            try {
                const res = await fetch(scopeOptionsUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (seq !== scopeRequestSeq) {
                    return;
                }
                if (json.status === 'success') {
                    applyScopeOptions(json.data);
                }
            } catch (e) {}
        }

        function onEntityPicked() {
            if (suppressEntityChange) {
                return;
            }
            refreshCycleScopes();
        }

        function onScopeChange() {
            const scope = pickerVal(reportScope) || (reportScope ? reportScope.value : '');
            if (scope === 'period' || scope === 'trimester' || scope === 'semester') {
                applyVisibleTermGroup(scope);
            } else {
                hideTermGroups(true);
            }

            if (scope === 'session' && scopeCycleHint) {
                scopeCycleHint.textContent = @json(__('reports.error_university_use_transcript'));
            } else if (scopeCycleHint && scope !== 'period' && scope !== 'trimester' && scope !== 'semester') {
                scopeCycleHint.textContent = defaultScopeHint;
            }
        }

        function bindReportSelectEvents() {
            if (!hasSelectpicker()) {
                studentSelect && studentSelect.addEventListener('change', onEntityPicked);
                classSelect && classSelect.addEventListener('change', onEntityPicked);
                reportScope && reportScope.addEventListener('change', onScopeChange);
                return;
            }
            $(studentSelect).off('changed.bs.select.reports change.reports').on('changed.bs.select.reports change.reports', onEntityPicked);
            $(classSelect).off('changed.bs.select.reports change.reports').on('changed.bs.select.reports change.reports', onEntityPicked);
            $(reportScope).off('changed.bs.select.reports change.reports').on('changed.bs.select.reports change.reports', onScopeChange);
        }

        bindReportSelectEvents();
        setTimeout(bindReportSelectEvents, 250);

        document.querySelectorAll('input[name="mode"]').forEach(function (radio) {
            radio.addEventListener('change', toggleMode);
        });

        function collectBulletinParams() {
            const bulk = isBulkMode();
            const params = new URLSearchParams();
            const studentId = pickerVal(studentSelect);
            const classId = pickerVal(classSelect);
            const scope = pickerVal(reportScope);
            const period = pickerVal(periodSelect);
            const trimester = pickerVal(trimesterSelect);
            const semester = pickerVal(semesterSelect);
            const typeVal = (document.getElementById('typeInput') || {}).value || '';

            if (bulk) {
                if (classId) params.set('class_section_id', classId);
            } else if (studentId) {
                params.set('student_id', studentId);
                // Include class when selected so Single uses the same section/session as Bulk.
                if (classId) params.set('class_section_id', classId);
            }
            if (typeVal) params.set('type', typeVal);
            if (scope === 'period' && period) params.set('period', period);
            if (scope === 'trimester' && trimester) params.set('trimester', trimester);
            if (scope === 'semester' && semester) params.set('semester', semester);

            return { params: params, bulk: bulk, studentId: studentId, classId: classId, scope: scope, period: period, trimester: trimester, semester: semester };
        }

        function bulletinUrlFromParams(params) {
            const qs = new URLSearchParams(params);
            qs.delete('check_only');
            return bulletinForm.action + '?' + qs.toString();
        }

        function openBulletinWindow(url) {
            const win = window.open(url, 'digitexBulletin');
            if (!win) {
                window.location.href = url;
                return null;
            }
            return win;
        }

        function applyLatestStage(latest) {
            const params = latest.params || {};
            if (params.period) {
                setPickerVal(reportScope, 'period');
                onScopeChange();
                setPickerVal(periodSelect, params.period);
            } else if (params.trimester) {
                setPickerVal(reportScope, 'trimester');
                onScopeChange();
                setPickerVal(trimesterSelect, String(params.trimester));
            } else if (params.semester) {
                setPickerVal(reportScope, 'semester');
                onScopeChange();
                setPickerVal(semesterSelect, String(params.semester));
            }
            const collected = collectBulletinParams();
            openBulletinWindow(bulletinUrlFromParams(collected.params));
        }

        function isInfoFeedback(data) {
            if (!data) {
                return true;
            }
            if (data.status === 'success') {
                return false;
            }
            if (data.feedback === 'error' && data.status === 'error') {
                return false;
            }
            return true;
        }

        function showReportFeedback(data) {
            const message = data.message || noRecordsMsg;

            const afterClose = function () {
                setTimeout(function () {
                    repairVisiblePickers();
                }, 80);
            };

            if (data.redirect_transcript && studentSelect && studentSelect.value) {
                Swal.fire({
                    icon: 'info',
                    title: @json(__('reports.transcript')),
                    text: message,
                    confirmButtonText: @json(__('reports.generate_transcript')),
                    confirmButtonColor: '#3085d6'
                }).then(function (result) {
                    afterClose();
                    if (result.isConfirmed) {
                        window.open(@json(route('reports.transcript')) + '?student_id=' + encodeURIComponent(studentSelect.value), '_blank');
                    }
                });
                return;
            }

            const latest = data.latest_official || latestOfficialStage;
            const swalOpts = {
                icon: 'info',
                title: (message === noRecordsMsg) ? noResultsTitle : noticeTitle,
                text: message,
                confirmButtonColor: '#3085d6'
            };
            if (latest && latest.params) {
                swalOpts.showCancelButton = true;
                swalOpts.confirmButtonText = @json(__('reports.open_latest_stage')).replace(':stage', latest.label);
                swalOpts.cancelButtonText = @json(__('reports.select_scope'));
            }
            Swal.fire(swalOpts).then(function (result) {
                afterClose();
                if (result.isConfirmed && latest && latest.params) {
                    applyLatestStage(latest);
                }
            });
        }

        toggleMode();
        setTimeout(repairVisiblePickers, 180);

        const bulletinForm = document.getElementById('bulletinForm');
        const btnBulletin = document.getElementById('btnBulletin');

        if (bulletinForm) {
            bulletinForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const collected = collectBulletinParams();
                const bulk = collected.bulk;
                const studentId = collected.studentId;
                const classId = collected.classId;
                const scope = collected.scope;
                const period = collected.period;
                const trimester = collected.trimester;
                const semester = collected.semester;

                if (scope === 'session') {
                    if (!studentId) {
                        showReportFeedback({
                            status: 'info',
                            feedback: 'info',
                            message: sessionNeedStudentMsg
                        });
                        return;
                    }
                    openBulletinWindow(@json(route('reports.transcript')) + '?student_id=' + encodeURIComponent(studentId));
                    return;
                }

                if (bulk && !classId) {
                    this.reportValidity();
                    return;
                }
                if (!bulk && !studentId) {
                    this.reportValidity();
                    return;
                }
                if (!scope) {
                    this.reportValidity();
                    return;
                }
                if (scope === 'period' && !period) {
                    this.reportValidity();
                    return;
                }
                if (scope === 'trimester' && !trimester) {
                    this.reportValidity();
                    return;
                }
                if (scope === 'semester' && !semester) {
                    this.reportValidity();
                    return;
                }

                const originalText = btnBulletin.innerHTML;
                btnBulletin.disabled = true;
                btnBulletin.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking...';

                const bulletinUrl = bulletinUrlFromParams(collected.params);
                // Open in this click so the browser does not treat it as a popup.
                let preview = window.open('about:blank', 'digitexBulletin');
                if (preview) {
                    try {
                        preview.document.write('<p style="font-family:sans-serif;padding:24px;color:#555;">Generating bulletin&hellip;</p>');
                        preview.document.close();
                    } catch (err) {}
                }

                const checkParams = new URLSearchParams(collected.params);
                checkParams.set('check_only', '1');

                fetch(this.action + '?' + checkParams.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function (response) {
                    return response.json().then(function (data) {
                        data._ok = response.ok;
                        return data;
                    });
                })
                .then(function (data) {
                    btnBulletin.disabled = false;
                    btnBulletin.innerHTML = originalText;
                    repairVisiblePickers();

                    if (data.status === 'success') {
                        if (preview && !preview.closed) {
                            preview.location.replace(bulletinUrl);
                        } else {
                            openBulletinWindow(bulletinUrl);
                        }
                    } else {
                        if (preview && !preview.closed) {
                            preview.close();
                        }
                        if (!data.message && data.errors) {
                            const first = Object.values(data.errors)[0];
                            data.message = Array.isArray(first) ? first[0] : first;
                        }
                        showReportFeedback(data);
                    }
                })
                .catch(function () {
                    btnBulletin.disabled = false;
                    btnBulletin.innerHTML = originalText;
                    repairVisiblePickers();
                    if (preview && !preview.closed) {
                        preview.close();
                    }
                    Swal.fire({
                        icon: 'info',
                        title: noticeTitle,
                        text: @json(__('reports.generic_error')),
                        confirmButtonColor: '#3085d6'
                    }).then(function () {
                        setTimeout(repairVisiblePickers, 80);
                    });
                });
            });
        }

        const transcriptForm = document.getElementById('transcriptForm');
        const btnTranscript = document.getElementById('btnTranscript');

        if (transcriptForm) {
            transcriptForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const studentField = transcriptForm.querySelector('[name="student_id"]');
                const studentId = pickerVal(studentField) || (studentField ? studentField.value : '');
                if (!studentId) {
                    this.reportValidity();
                    return;
                }

                const originalText = btnTranscript.innerHTML;
                btnTranscript.disabled = true;
                btnTranscript.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking...';

                const transcriptUrl = transcriptForm.action + '?student_id=' + encodeURIComponent(studentId);
                let preview = window.open('about:blank', 'digitexTranscript');
                if (preview) {
                    try {
                        preview.document.write('<p style="font-family:sans-serif;padding:24px;color:#555;">Generating transcript&hellip;</p>');
                        preview.document.close();
                    } catch (err) {}
                }

                fetch(transcriptForm.action + '?student_id=' + encodeURIComponent(studentId) + '&check_only=1', {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    btnTranscript.disabled = false;
                    btnTranscript.innerHTML = originalText;

                    if (data.status === 'success') {
                        if (preview && !preview.closed) {
                            preview.location.replace(transcriptUrl);
                        } else {
                            openBulletinWindow(transcriptUrl);
                        }
                    } else {
                        if (preview && !preview.closed) {
                            preview.close();
                        }
                        showReportFeedback(data);
                    }
                })
                .catch(function () {
                    btnTranscript.disabled = false;
                    btnTranscript.innerHTML = originalText;
                    if (preview && !preview.closed) {
                        preview.close();
                    }
                    Swal.fire({
                        icon: 'info',
                        title: noticeTitle,
                        text: @json(__('reports.generic_error')),
                        confirmButtonColor: '#3085d6'
                    });
                });
            });
        }
    });
</script>
@endsection
