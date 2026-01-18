@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('marks.enter_marks') }}</h4>
                    <p class="mb-0">{{ __('marks.manage_subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="text-primary fw-bold"><i class="fa fa-filter me-2"></i> {{ __('marks.select_criteria') }}</h5>
                    </div>
                    <div class="card-body pt-3">
                        <div class="row">
                            {{-- 1. Exam --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.select_exam') }} <span class="text-danger">*</span></label>
                                @php $examCount = count($exams ?? []); @endphp
                                @if($examCount > 1)
                                    <select id="exam_select" class="form-control default-select">
                                        <option value="">-- {{ __('marks.select_exam') }} --</option>
                                        @foreach($exams as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                @elseif($examCount == 1)
                                    @php $id = array_key_first($exams->toArray()); @endphp
                                    <input type="hidden" id="exam_select" value="{{ $id }}">
                                    <input type="text" class="form-control" value="{{ $exams[$id] }}" readonly disabled>
                                @else
                                    <p class="text-danger">No ongoing exams found.</p>
                                @endif
                            </div>

                            {{-- 2. Grade (Year Level) --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.grade') }} <span class="text-danger">*</span></label>
                                <select id="grade_select" class="form-control default-select" disabled>
                                    <option value="">-- {{ __('marks.select_grade') }} --</option>
                                </select>
                            </div>

                            {{-- 3. Section / Option --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.section_option') }} <span class="text-danger">*</span></label>
                                <select id="section_select" class="form-control default-select" disabled>
                                    <option value="">-- {{ __('marks.select_section') }} --</option>
                                </select>
                            </div>

                            {{-- 4. Subject --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.subject') }} <span class="text-danger">*</span></label>
                                <select id="subject_select" class="form-control default-select" disabled>
                                    <option value="">-- {{ __('marks.select_subject') }} --</option>
                                </select>
                                <div id="total_marks_display" class="mt-2 text-info fw-bold d-none" style="font-size: 0.85rem;">
                                    {{ __('marks.total_marks') }}: <span id="total_marks_value" class="text-dark"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Container --}}
        <div id="marks_container" class="d-none">
            <form id="marksForm" action="{{ route('marks.store') }}" method="POST">
                @csrf
                <input type="hidden" name="exam_id" id="form_exam_id">
                <input type="hidden" name="class_section_id" id="form_section_id">
                <input type="hidden" name="subject_id" id="form_subject_id">

                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="row align-items-center w-100 g-3">
                            <div class="col-md-6">
                                <div class="d-flex flex-wrap gap-4 mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="text-uppercase fw-bold text-muted small me-2">{{ __('marks.grade') }}:</span>
                                        <span class="fw-bold text-dark" id="header_grade">-</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="text-uppercase fw-bold text-muted small me-2">{{ __('marks.section') }}:</span>
                                        <span class="fw-bold text-dark" id="header_section">-</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="text-uppercase fw-bold text-muted small me-2">{{ __('marks.teacher') }}:</span>
                                        <span class="fw-bold text-primary" id="header_teacher">-</span>
                                    </div>
                                </div>
                                {{-- Total Students Badge --}}
                                <span class="badge badge-light border text-dark">
                                    <i class="fa fa-users me-1"></i> {{ __('marks.total_students') }}: <span id="total_students_count" class="fw-bold">0</span>
                                </span>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end gap-2">
                                <div class="input-group shadow-sm me-2" style="max-width: 250px;">
                                    <span class="input-group-text bg-light border-end-0 ps-3"><i class="fa fa-search text-muted"></i></span>
                                    <input type="text" id="table_search" class="form-control border-start-0 bg-light" placeholder="{{ __('marks.search_student') }}">
                                </div>
                                {{-- Print Award List Button --}}
                                <button type="button" id="print_award_list" class="btn btn-secondary btn-sm shadow">
                                    <i class="fa fa-print me-1"></i> {{ __('marks.award_list') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4" width="5%">#</th>
                                        <th width="35%">{{ __('marks.student_name') }}</th>
                                        <th width="20%">{{ __('marks.admission_no') }}</th>
                                        <th width="25%">{{ __('marks.marks_obtained') }} <span id="table_max_label" class="text-muted small"></span></th>
                                        <th class="text-center" width="15%">{{ __('marks.is_absent') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="student_table_body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-end py-4 pe-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small ps-4">
                                <i class="fa fa-info-circle me-1"></i> {{ __('marks.auto_save_info') }}
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg shadow px-5">
                                <i class="fa fa-save me-2"></i> {{ __('marks.save_marks') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="loading_spinner" class="text-center my-5 d-none">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">{{ __('marks.load_students') }}...</p>
        </div>
        
        <div id="empty_state" class="text-center my-5 d-none">
            <div class="alert alert-warning d-inline-block px-5">
                <i class="fa fa-exclamation-circle me-2"></i> No students found.
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        const examSel = $('#exam_select');
        const gradeSel = $('#grade_select');
        const sectionSel = $('#section_select');
        const subjectSel = $('#subject_select');
        
        function updateUI(el) { 
            if($.fn.select2 && !el.hasClass("select2-hidden-accessible")) {
                el.select2();
            } else if ($.fn.select2) {
                el.trigger('change.select2'); 
            }
            if($.fn.selectpicker) el.selectpicker('refresh');
        }

        // --- Step 1: Load Grades ---
        function loadGrades() {
            let id = examSel.val();
            gradeSel.prop('disabled', true).html('<option value="">Loading...</option>');
            sectionSel.prop('disabled', true).html('<option value="">-- {{ __("marks.select_section") }} --</option>');
            subjectSel.prop('disabled', true).html('<option value="">-- {{ __("marks.select_subject") }} --</option>');
            $('#marks_container, #empty_state').addClass('d-none');
            updateUI(gradeSel); updateUI(sectionSel); updateUI(subjectSel);

            if(!id) return;

            $.get("{{ route('marks.get_grades') }}", {exam_id: id}, function(data) {
                gradeSel.html('<option value="">-- {{ __("marks.select_grade") }} --</option>');
                Object.entries(data).forEach(([id, name]) => gradeSel.append(new Option(name, id)));
                gradeSel.prop('disabled', false); updateUI(gradeSel);
            });
        }
        
        examSel.on('change', loadGrades);
        if(examSel.val()) loadGrades();

        // --- Helper: Fetch Subjects ---
        function fetchSubjects(gradeId, sectionId = null) {
            subjectSel.prop('disabled', true).html('<option value="">Loading...</option>');
            updateUI(subjectSel);
            
            let params = { grade_level_id: gradeId };
            if(sectionId) params.class_section_id = sectionId;

            $.get("{{ route('marks.get_subjects') }}", params, function(data) {
                subjectSel.html('<option value="">-- {{ __("marks.select_subject") }} --</option>');
                data.forEach(s => {
                    let opt = new Option(s.name, s.id);
                    $(opt).data('total', s.total_marks).data('teacher', s.teacher_name);
                    subjectSel.append(opt);
                });
                subjectSel.prop('disabled', false); 
                updateUI(subjectSel);
            });
        }

        // --- Step 2: Grade Change ---
        gradeSel.on('change', function() {
            let id = $(this).val();
            sectionSel.prop('disabled', true).html('<option value="">Loading...</option>');
            // Reset subjects
            subjectSel.prop('disabled', true).html('<option value="">-- {{ __("marks.select_subject") }} --</option>');
            updateUI(sectionSel); updateUI(subjectSel);
            
            if(!id) return;

            // Load Sections
            $.get("{{ route('marks.get_sections') }}", {grade_level_id: id}, function(data) {
                sectionSel.html('<option value="">-- {{ __("marks.select_section") }} --</option>');
                Object.entries(data).forEach(([id, name]) => sectionSel.append(new Option(name, id)));
                sectionSel.prop('disabled', false); updateUI(sectionSel);
            });

            // Load Subjects (General/Initial)
            fetchSubjects(id, null);
        });

        // --- Step 2.5: Section Change ---
        sectionSel.on('change', function() {
            let secId = $(this).val();
            let gradeId = gradeSel.val();
            
            // Reload subjects specifically for this section
            if(gradeId && secId) {
                fetchSubjects(gradeId, secId);
            }
            
            // If user had already selected a subject and section, try load table
            tryLoadStudents(); 
        });

        // --- Step 3: Load Students ---
        function tryLoadStudents() {
            let subId = subjectSel.val();
            let secId = sectionSel.val();
            let exId = examSel.val();
            
            if(!subId || !secId || !exId) { $('#marks_container').addClass('d-none'); return; }

            let subjOpt = subjectSel.find(':selected');
            $('#total_marks_value').text(subjOpt.data('total'));
            $('#total_marks_display').removeClass('d-none');
            $('#table_max_label').text('(Max: ' + subjOpt.data('total') + ')');
            
            $('#header_grade').text(gradeSel.find(':selected').text());
            $('#header_section').text(sectionSel.find(':selected').text());
            $('#header_teacher').text(subjOpt.data('teacher'));

            $('#form_exam_id').val(exId); $('#form_section_id').val(secId); $('#form_subject_id').val(subId);

            $('#loading_spinner').removeClass('d-none'); $('#marks_container').addClass('d-none'); $('#empty_state').addClass('d-none');

            $.get("{{ route('marks.get_students') }}", {exam_id: exId, class_section_id: secId, subject_id: subId}, function(res) {
                $('#loading_spinner').addClass('d-none');
                if (res.students && res.students.length > 0) {
                    let rows = '';
                    let maxMarks = subjOpt.data('total') || 100;
                    $('#total_students_count').text(res.students.length);

                    res.students.forEach((s, i) => {
                        let m = res.marks[s.id] || {marks_obtained: '', is_absent: 0};
                        rows += `<tr class="s-row"><td class="ps-4 fw-bold">${i+1}</td><td><span class="s-name fw-bold text-dark">${s.name}</span></td><td><span class="s-adm badge badge-light badge-sm text-dark">${s.admission_number}</span></td><td><input type="number" name="marks[${s.id}]" class="form-control mark-input w-50 border-secondary" value="${m.is_absent ? '' : m.marks_obtained}" ${m.is_absent ? 'disabled' : ''} max="${maxMarks}" step="0.01" placeholder="0-${maxMarks}"></td><td class="text-center"><div class="form-check d-inline-block"><input type="checkbox" name="absent[${s.id}]" class="form-check-input abs-check" ${m.is_absent ? 'checked' : ''}></div></td></tr>`;
                    });
                    $('#student_table_body').html(rows); $('#marks_container').removeClass('d-none');
                } else {
                    $('#empty_state').removeClass('d-none');
                }
            }).fail(function() {
                $('#loading_spinner').addClass('d-none');
                Swal.fire({icon: 'error', title: 'Error', text: 'Failed to load data.'});
            });
        }

        subjectSel.on('change', tryLoadStudents);

        // --- Logic: Print Award List ---
        $('#print_award_list').click(function() {
            let examId = examSel.val();
            let classId = sectionSel.val();
            let subjectId = subjectSel.val();

            if (!examId || !classId || !subjectId) {
                Swal.fire({icon: 'warning', title: 'Incomplete Selection', text: 'Please select Exam, Grade, Section, and Subject first.'});
                return;
            }

            let url = "{{ route('exams.print_award_list') }}?exam_id=" + examId + "&class_section_id=" + classId + "&subject_id=" + subjectId;
            window.open(url, '_blank');
        });

        // --- Logic: Absent Toggle ---
        $(document).on('change', '.abs-check', function() {
            let input = $(this).closest('tr').find('.mark-input');
            if($(this).is(':checked')) { input.prop('disabled', true).val('').removeClass('is-invalid'); } 
            else { input.prop('disabled', false).focus(); }
        });

        // --- Logic: Validation ---
        $(document).on('input', '.mark-input', function() {
            let max = parseFloat($('#total_marks_value').text()) || 100;
            if(parseFloat($(this).val()) > max) $(this).addClass('is-invalid'); else $(this).removeClass('is-invalid');
        });

        // --- Logic: Search ---
        $('#table_search').on('keyup', function() {
            let v = this.value.toLowerCase();
            $('.s-row').each(function() {
                let t = $(this).find('.s-name').text().toLowerCase() + " " + $(this).find('.s-adm').text().toLowerCase();
                $(this).toggle(t.includes(v));
            });
        });

        // --- Logic: Save (UPDATED: Empty Mark Validation) ---
        $('#marksForm').on('submit', function(e) {
            e.preventDefault();
            let max = parseFloat($('#total_marks_value').text()) || 100;
            let err = false;
            let emptyErr = false;

            $('.mark-input').each(function() { 
                let val = $(this).val();
                let isAbsent = $(this).closest('tr').find('.abs-check').is(':checked');

                // Check Max
                if(parseFloat(val) > max) { $(this).addClass('is-invalid'); err = true; } 
                
                // Check Empty (unless absent)
                // Use strict check for empty string to avoid false positives on 0
                if(!isAbsent && (val === '' || val === null)) {
                    $(this).addClass('is-invalid');
                    emptyErr = true;
                } else if(!err) { // Only remove if no max error
                    $(this).removeClass('is-invalid');
                }
            });

            if(err) { Swal.fire({icon: 'warning', title: 'Invalid Marks', text: `Marks cannot exceed ${max}`}); return; }
            
            // New Validation Alert
            if(emptyErr) { 
                Swal.fire({
                    icon: 'warning', 
                    title: 'Missing Values', 
                    text: 'Marks cannot be empty unless the student is marked as Absent.'
                }); 
                return; 
            }

            let btn = $(this).find('button[type="submit"]'); 
            let txt = btn.text();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
            
            $.post(this.action, $(this).serialize(), function(r) {
                btn.prop('disabled', false).text(txt);
                Swal.fire({
                    icon: 'success', 
                    title: 'Success!', 
                    text: r.message, 
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                });
            }).fail(x => { 
                btn.prop('disabled', false).text(txt); 
                Swal.fire('Error', x.responseJSON.message || 'Save failed', 'error'); 
            });
        });
    });
</script>
@endsection