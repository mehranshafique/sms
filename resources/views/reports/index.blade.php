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
                        
                        <form action="{{ route('reports.bulletin') }}" method="GET" target="_blank" id="bulletinForm">
                            
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
                                        <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Bulk: Class Select --}}
                            <div class="mb-3 d-none" id="classInputGroup">
                                <label class="form-label">{{ __('class_section.page_title') }} <span class="text-danger">*</span></label>
                                <select class="form-control default-select" name="class_section_id" id="classSelect">
                                    <option value="">{{ __('invoice.select_class') }}</option>
                                    @foreach($classes as $cls)
                                        <option value="{{ $cls->id }}">{{ $cls->gradeLevel->name }} - {{ $cls->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Report Scope --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('reports.report_scope') ?? 'Report Scope' }} <span class="text-danger">*</span></label>
                                <select class="form-control default-select" name="report_scope" id="reportScope" required>
                                    <option value="">-- {{ __('reports.select_scope') ?? 'Select Scope' }} --</option>
                                    <option value="period">{{ __('reports.period_report') ?? 'Period Report' }}</option>
                                    
                                    @if(isset($institutionType) && $institutionType !== 'university')
                                        <option value="trimester">{{ __('reports.trimester_report') ?? 'Trimester Report' }}</option>
                                        <option value="semester">{{ __('reports.semester_report') ?? 'Semester Report' }}</option>
                                    @endif
                                </select>
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
                                        <option value="p5">{{ __('reports.period') }} 5</option>
                                        <option value="p6">{{ __('reports.period') }} 6</option>
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

            {{-- 2. Transcript Card (Hidden for Primary/Secondary Only Schools) --}}
            @if(isset($institutionType) && in_array($institutionType, ['university', 'mixed']))
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pb-0">
                        <h4 class="card-title text-secondary">{{ __('reports.transcript') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4 fs-13">{{ __('reports.transcript_description') ?? 'Generate a comprehensive academic history report (Cumulative).' }}</p>
                        
                        <form action="{{ route('reports.transcript') }}" method="GET" target="_blank" id="transcriptForm">
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
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modeRadios = document.querySelectorAll('input[name="mode"]');
        const studentGroup = document.getElementById('studentInputGroup');
        const classGroup = document.getElementById('classInputGroup');
        const studentSelect = document.getElementById('studentSelect');
        const classSelect = document.getElementById('classSelect');
        const reportScope = document.getElementById('reportScope');
        const periodSelect = document.getElementById('periodSelect');
        const trimesterSelect = document.getElementById('trimesterSelect');
        const semesterSelect = document.getElementById('semesterSelect');

        function refreshPicker(selectEl) {
            if (!selectEl) {
                return;
            }
            if (typeof $ !== 'undefined' && $(selectEl).is('select') && $.fn.selectpicker) {
                $(selectEl).selectpicker('refresh');
            }
        }

        function setPickerEnabled(selectEl, enabled) {
            if (!selectEl) {
                return;
            }
            selectEl.disabled = !enabled;
            const $el = $(selectEl);
            if ($.fn.selectpicker && $el.data('selectpicker')) {
                enabled ? $el.selectpicker('enable') : $el.selectpicker('disable');
                refreshPicker(selectEl);
            }
        }

        function toggleMode() {
            const isBulk = document.getElementById('modeBulk').checked;
            if (isBulk) {
                studentGroup.classList.add('d-none');
                classGroup.classList.remove('d-none');
                studentSelect.required = false;
                studentSelect.value = '';
                setPickerEnabled(studentSelect, false);
                classSelect.required = true;
                setPickerEnabled(classSelect, true);
                refreshPicker(classSelect);
            } else {
                classGroup.classList.add('d-none');
                studentGroup.classList.remove('d-none');
                classSelect.required = false;
                classSelect.value = '';
                setPickerEnabled(classSelect, false);
                studentSelect.required = true;
                setPickerEnabled(studentSelect, true);
                refreshPicker(studentSelect);
            }
            refreshPicker(reportScope);
            refreshPicker(periodSelect);
            refreshPicker(trimesterSelect);
            refreshPicker(semesterSelect);
        }
        toggleMode();

        modeRadios.forEach(radio => radio.addEventListener('change', toggleMode));

        if (reportScope) {
            reportScope.addEventListener('change', function() {
                const scope = this.value;

                document.getElementById('periodGroup').classList.add('d-none');
                document.getElementById('trimesterGroup').classList.add('d-none');
                document.getElementById('semesterGroup').classList.add('d-none');
                periodSelect.required = false;
                trimesterSelect.required = false;
                semesterSelect.required = false;
                periodSelect.value = '';
                trimesterSelect.value = '';
                semesterSelect.value = '';
                document.getElementById('typeInput').value = 'term';

                if (scope === 'period') {
                    document.getElementById('periodGroup').classList.remove('d-none');
                    periodSelect.required = true;
                    document.getElementById('typeInput').value = 'period';
                } else if (scope === 'trimester') {
                    document.getElementById('trimesterGroup').classList.remove('d-none');
                    trimesterSelect.required = true;
                } else if (scope === 'semester') {
                    document.getElementById('semesterGroup').classList.remove('d-none');
                    semesterSelect.required = true;
                }

                refreshPicker(reportScope);
                refreshPicker(periodSelect);
                refreshPicker(trimesterSelect);
                refreshPicker(semesterSelect);
            });
        }

        // --- AJAX CHECK: Bulletin Form ---
        const bulletinForm = document.getElementById('bulletinForm');
        const btnBulletin = document.getElementById('btnBulletin');

        if (bulletinForm) {
            let bulletinReady = false;
            bulletinForm.addEventListener('submit', function(e) {
                if (bulletinReady) {
                    return;
                }
                e.preventDefault();

                if (!this.checkValidity()) {
                    this.reportValidity();
                    return;
                }

                const originalText = btnBulletin.innerHTML;
                btnBulletin.disabled = true;
                btnBulletin.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking...';

                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                params.append('check_only', '1');

                fetch(this.action + '?' + params.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    btnBulletin.disabled = false;
                    btnBulletin.innerHTML = originalText;

                    if (data.status === 'error') {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('reports.error_occurred')),
                            text: data.message || @json(__('reports.no_records_found')),
                            confirmButtonColor: '#d33'
                        });
                    } else {
                        bulletinReady = true;
                        HTMLFormElement.prototype.submit.call(bulletinForm);
                        bulletinReady = false;
                    }
                })
                .catch(function() {
                    btnBulletin.disabled = false;
                    btnBulletin.innerHTML = originalText;
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('reports.error_occurred')),
                        text: @json(__('reports.generic_error'))
                    });
                });
            });
        }

        // --- AJAX CHECK: Transcript Form ---
        const transcriptForm = document.getElementById('transcriptForm');
        const btnTranscript = document.getElementById('btnTranscript');

        if (transcriptForm) {
            let transcriptReady = false;
            transcriptForm.addEventListener('submit', function(e) {
                if (transcriptReady) {
                    return;
                }
                e.preventDefault();

                if (!this.checkValidity()) {
                    this.reportValidity();
                    return;
                }

                const originalText = btnTranscript.innerHTML;
                btnTranscript.disabled = true;
                btnTranscript.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking...';

                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                params.append('check_only', '1');

                fetch(this.action + '?' + params.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    btnTranscript.disabled = false;
                    btnTranscript.innerHTML = originalText;

                    if (data.status === 'error') {
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('reports.error_occurred')),
                            text: data.message || @json(__('reports.no_records_found')),
                            confirmButtonColor: '#d33'
                        });
                    } else {
                        transcriptReady = true;
                        HTMLFormElement.prototype.submit.call(transcriptForm);
                        transcriptReady = false;
                    }
                })
                .catch(function() {
                    btnTranscript.disabled = false;
                    btnTranscript.innerHTML = originalText;
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('reports.error_occurred')),
                        text: @json(__('reports.generic_error'))
                    });
                });
            });
        }
    });
</script>
@endsection