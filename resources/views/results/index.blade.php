@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('results.student_result_card') }}</h4>
                    <p class="mb-0">{{ __('results.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="text-white mb-0"><i class="fa fa-search me-2"></i> {{ __('results.find_result') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('results.print') }}" method="GET" target="_blank" id="resultForm">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">{{ __('results.select_exam') }} <span class="text-danger">*</span></label>
                                    <select name="exam_id" id="exam_select" class="form-control default-select" required>
                                        <option value="">{{ __('results.select_exam_placeholder') }}</option>
                                        @if(isset($exams) && count($exams) > 0)
                                            @foreach($exams as $id => $name)
                                                <option value="{{ $id }}">
                                                    {{ is_array($name) ? implode(' ', $name) : (is_object($name) ? $name->name ?? 'Exam' : $name) }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>{{ __('results.no_exams_found') }}</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">{{ __('results.select_class') }} <span class="text-danger">*</span></label>
                                    <select id="class_select" class="form-control default-select" disabled>
                                        <option value="">{{ __('results.select_class_first') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">{{ __('results.select_student') }} <span class="text-danger">*</span></label>
                                    <select name="student_id" id="student_select" class="form-control default-select" required disabled>
                                        <option value="">{{ __('results.select_student_first') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-print me-2"></i> {{ __('results.generate_btn') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(has_ai_access())
        <div class="row mt-3">
            <div class="col-xl-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="la la-magic text-primary me-2"></i> {{ __('ai.tools.bulk_report_comments') }}</h5>
                        <p class="text-muted small mb-0">{{ __('ai.tools.bulk_report_comments_desc') }}</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">{{ __('results.select_exam') }}</label>
                                <select id="ai_bulk_exam" class="form-control default-select">
                                    <option value="">{{ __('results.select_exam_placeholder') }}</option>
                                    @if(isset($exams))
                                        @foreach($exams as $id => $name)
                                            <option value="{{ $id }}">{{ is_array($name) ? implode(' ', $name) : $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">{{ __('results.select_class') }}</label>
                                <select id="ai_bulk_class" class="form-control default-select" disabled>
                                    <option value="">{{ __('results.select_class_first') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100 ai-embed-btn" id="ai-bulk-comments-btn"
                                    data-ai-tool="bulk_report_comments"
                                    data-ai-params="{}"
                                    data-ai-fields='{"exam_id":"#ai_bulk_exam","class_section_id":"#ai_bulk_class"}'
                                    data-ai-panel="#ai-bulk-comments-output">
                                    <i class="la la-magic"></i> {{ __('ai.btn_bulk_comments') }}
                                </button>
                            </div>
                        </div>
                        <div class="ai-embed-panel mt-3" id="ai-bulk-comments-output"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if ($.fn.selectpicker) {
                    $(element).selectpicker('refresh');
                }
            }
        }

        const examSelect = document.getElementById('exam_select');
        const classSelect = document.getElementById('class_select');
        const studentSelect = document.getElementById('student_select');

        if (examSelect) {
            examSelect.addEventListener('change', function() {
                const examId = this.value;

                classSelect.innerHTML = `<option value="">${@json(__('results.loading'))}</option>`;
                classSelect.disabled = true;
                studentSelect.innerHTML = `<option value="">${@json(__('results.select_class_first'))}</option>`;
                studentSelect.disabled = true;
                refreshSelect(classSelect);
                refreshSelect(studentSelect);

                if (!examId) {
                    classSelect.innerHTML = `<option value="">${@json(__('results.select_class_first'))}</option>`;
                    refreshSelect(classSelect);
                    return;
                }

                fetch(`{{ route('results.get_classes') }}?exam_id=${encodeURIComponent(examId)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(res => res.json())
                    .then(data => {
                        classSelect.innerHTML = `<option value="">${@json(__('results.select_class_placeholder'))}</option>`;
                        Object.entries(data).forEach(([id, name]) => {
                            const safeName = (typeof name === 'object') ? (name.name || JSON.stringify(name)) : name;
                            classSelect.add(new Option(safeName, id));
                        });
                        classSelect.disabled = false;
                        refreshSelect(classSelect);
                    })
                    .catch(() => {
                        classSelect.innerHTML = `<option value="">${@json(__('results.error_loading_classes'))}</option>`;
                        refreshSelect(classSelect);
                    });
            });
        }

        if (classSelect) {
            classSelect.addEventListener('change', function() {
                const classId = this.value;
                const examId = examSelect ? examSelect.value : '';

                studentSelect.innerHTML = `<option value="">${@json(__('results.loading'))}</option>`;
                studentSelect.disabled = true;
                refreshSelect(studentSelect);

                if (!classId || !examId) {
                    studentSelect.innerHTML = `<option value="">${@json(__('results.select_student_first'))}</option>`;
                    refreshSelect(studentSelect);
                    return;
                }

                const url = `{{ route('results.get_students') }}?exam_id=${encodeURIComponent(examId)}&class_section_id=${encodeURIComponent(classId)}`;
                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.json())
                    .then(data => {
                        studentSelect.innerHTML = `<option value="">${@json(__('results.select_student_placeholder'))}</option>`;
                        data.forEach(student => {
                            const label = `${student.name} (${student.roll_number})`;
                            studentSelect.add(new Option(label, student.id));
                        });
                        studentSelect.disabled = false;
                        refreshSelect(studentSelect);
                    })
                    .catch(() => {
                        studentSelect.innerHTML = `<option value="">${@json(__('results.error_loading_students'))}</option>`;
                        refreshSelect(studentSelect);
                    });
            });
        }

        @if(has_ai_access())
        const aiBulkExam = document.getElementById('ai_bulk_exam');
        const aiBulkClass = document.getElementById('ai_bulk_class');

        if (aiBulkExam && aiBulkClass) {
            aiBulkExam.addEventListener('change', function() {
                const examId = this.value;
                aiBulkClass.innerHTML = `<option value="">${@json(__('results.loading'))}</option>`;
                aiBulkClass.disabled = true;
                refreshSelect(aiBulkClass);

                if (!examId) {
                    aiBulkClass.innerHTML = `<option value="">${@json(__('results.select_class_first'))}</option>`;
                    refreshSelect(aiBulkClass);
                    return;
                }

                fetch(`{{ route('results.get_classes') }}?exam_id=${encodeURIComponent(examId)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(res => res.json())
                    .then(data => {
                        aiBulkClass.innerHTML = `<option value="">${@json(__('results.select_class_placeholder'))}</option>`;
                        Object.entries(data).forEach(([id, name]) => {
                            aiBulkClass.add(new Option(name, id));
                        });
                        aiBulkClass.disabled = false;
                        refreshSelect(aiBulkClass);
                    });
            });
        }
        @endif

        const resultForm = document.getElementById('resultForm');
        if (resultForm) {
            let resultReady = false;
            resultForm.addEventListener('submit', function(e) {
                if (resultReady) {
                    return;
                }
                e.preventDefault();

                if (!this.checkValidity()) {
                    this.reportValidity();
                    return;
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> {{ __('results.loading') }}';
                }

                const params = new URLSearchParams(new FormData(this));
                params.append('check_only', '1');

                fetch(this.action + '?' + params.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }

                    if (data.status === 'success') {
                        resultReady = true;
                        HTMLFormElement.prototype.submit.call(resultForm);
                        resultReady = false;
                    } else {
                        const isInfo = data.status === 'info' || data.feedback === 'info';
                        Swal.fire({
                            icon: isInfo ? 'info' : 'error',
                            title: isInfo
                                ? @json(__('results.no_results_title'))
                                : @json(__('results.error_occurred')),
                            text: data.message || @json(__('results.no_marks_found_error')),
                            confirmButtonColor: isInfo ? '#3085d6' : '#d33'
                        });
                    }
                })
                .catch(function() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: @json(__('results.error_occurred')),
                        text: @json(__('results.generic_error'))
                    });
                });
            });
        }
    });
</script>
@endsection
