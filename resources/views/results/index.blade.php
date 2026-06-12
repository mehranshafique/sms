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
                                {{-- 1. Select Exam --}}
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

                                {{-- 2. Select Class --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">{{ __('results.select_class') }} <span class="text-danger">*</span></label>
                                    <select id="class_select" class="form-control default-select" disabled>
                                        <option value="">{{ __('results.select_class_first') }}</option>
                                    </select>
                                </div>

                                {{-- 3. Select Student --}}
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

        @if(!empty($planCtx['has_ai']))
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
<script>
    $(document).ready(function() {
        
        // --- Helper Function (Matched exactly to _form.blade.php) ---
        function refreshSelect(element) {
            // Check for jQuery and NiceSelect/Bootstrap Select
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if($.fn.niceSelect) {
                    $(element).niceSelect('update');
                } else if ($.fn.selectpicker) { 
                     $(element).selectpicker('refresh');
                }
            }
        }

        // Exam -> Class
        $('#exam_select').on('change', function() {
            let examId = $(this).val();
            let classSelect = $('#class_select');
            let studentSelect = $('#student_select');
            
            // Reset Class Select
            classSelect.html('<option>{{ __('results.loading') }}</option>').prop('disabled', true);
            refreshSelect(classSelect);

            // Reset Student Select
            studentSelect.html('<option value="">{{ __('results.select_class_first') }}</option>').prop('disabled', true);
            refreshSelect(studentSelect);

            if(examId) {
                $.get("{{ route('results.get_classes') }}", { exam_id: examId }, function(data) {
                    // Clear and add placeholder
                    classSelect.empty();
                    classSelect.append('<option value="">{{ __('results.select_class_placeholder') }}</option>');
                    
                    $.each(data, function(id, name) {
                        let safeName = (typeof name === 'object') ? (name.name || JSON.stringify(name)) : name;
                        // Use standard DOM option creation to ensure compatibility
                        classSelect.append(new Option(safeName, id));
                    });
                    
                    classSelect.prop('disabled', false);
                    refreshSelect(classSelect); // Update UI
                }).fail(function() {
                     classSelect.html('<option value="">{{ __('results.error_loading_classes') }}</option>');
                     refreshSelect(classSelect);
                });
            }
        });

        // Class -> Students
        $('#class_select').on('change', function() {
            let classId = $(this).val();
            let examId = $('#exam_select').val();
            let studentSelect = $('#student_select');

            studentSelect.html('<option>{{ __('results.loading') }}</option>').prop('disabled', true);
            refreshSelect(studentSelect);

            if(classId && examId) {
                let url = "{{ route('results.get_students') }}"; 
                
                $.get(url, { exam_id: examId, class_section_id: classId }, function(data) {
                    studentSelect.empty();
                    studentSelect.append('<option value="">{{ __('results.select_student_placeholder') }}</option>');
                    
                    $.each(data, function(index, student) {
                        let label = `${student.name} (${student.roll_number})`;
                        studentSelect.append(new Option(label, student.id));
                    });
                    
                    studentSelect.prop('disabled', false);
                    refreshSelect(studentSelect); // Update UI
                }).fail(function() {
                    studentSelect.html('<option value="">{{ __('results.error_loading_students') }}</option>');
                    refreshSelect(studentSelect);
                });
            }
        });

        @if(!empty($planCtx['has_ai']))
        $('#ai_bulk_exam').on('change', function() {
            let examId = $(this).val();
            let classSelect = $('#ai_bulk_class');
            classSelect.html('<option>{{ __('results.loading') }}</option>').prop('disabled', true);
            refreshSelect(classSelect);
            if (examId) {
                $.get("{{ route('results.get_classes') }}", { exam_id: examId }, function(data) {
                    classSelect.empty().append('<option value="">{{ __('results.select_class_placeholder') }}</option>');
                    $.each(data, function(id, name) {
                        classSelect.append(new Option(name, id));
                    });
                    classSelect.prop('disabled', false);
                    refreshSelect(classSelect);
                });
            }
        });
        @endif
    });
</script>
@endsection