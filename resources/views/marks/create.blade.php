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

        {{-- Filter Section --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="text-primary fw-bold"><i class="fa fa-filter me-2"></i> Select Criteria</h5>
                    </div>
                    <div class="card-body pt-3">
                        <div class="row">
                            {{-- 1. Exam (Smart Display) --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.select_exam') }} <span class="text-danger">*</span></label>
                                
                                @php
                                    $examCount = count($exams);
                                    // Prepare single exam data if count is 1
                                    $singleExamId = null;
                                    $singleExamName = '';
                                    if($examCount === 1) {
                                        foreach($exams as $id => $name) {
                                            $singleExamId = $id;
                                            $singleExamName = $name;
                                        }
                                    }
                                @endphp

                                @if($examCount > 1)
                                    {{-- Multiple Exams: Show Dropdown --}}
                                    <select id="exam_select" class="form-control default-select">
                                        <option value="">-- {{ __('marks.select_exam') }} --</option>
                                        @foreach($exams as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{-- Single Exam: Show Text & Auto-Select via Hidden Input --}}
                                    <input type="hidden" id="exam_select" value="{{ $singleExamId }}">
                                    <input type="text" class="form-control" value="{{ $singleExamName }}" readonly disabled style="background-color: #f8f9fa;">
                                @endif
                            </div>

                            {{-- 2. Class (Loaded via AJAX) --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.select_class') }} <span class="text-danger">*</span></label>
                                <select id="class_select" class="form-control default-select" disabled>
                                    <option value="">-- {{ $examCount > 1 ? 'Select Exam First' : 'Loading...' }} --</option>
                                </select>
                            </div>

                            {{-- 3. Subject (Loaded via AJAX) --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.select_subject') }} <span class="text-danger">*</span></label>
                                <select id="subject_select" class="form-control default-select" disabled>
                                    <option value="">-- Select Class First --</option>
                                </select>
                                {{-- Total Marks Display --}}
                                <div id="total_marks_display" class="mt-2 text-info fw-bold d-none" style="font-size: 0.9rem;">
                                    Total Marks: <span id="total_marks_value" class="text-dark"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loading Spinner --}}
        <div id="loading_spinner" class="text-center my-5 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Fetching student list...</p>
        </div>

        {{-- Marks Entry Table (Hidden Initially) --}}
        <div id="marks_container" class="d-none">
            <form action="{{ route('marks.store') }}" method="POST" id="marksForm">
                @csrf
                <input type="hidden" name="exam_id" id="form_exam_id">
                <input type="hidden" name="class_section_id" id="form_class_id">
                <input type="hidden" name="subject_id" id="form_subject_id">

                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0 fw-bold fs-18 text-primary"><i class="fa fa-users me-2"></i> {{ __('marks.student_list') }}</h4>
                        <span class="badge badge-primary light" id="student_count_badge">0 Students</span>
                    </div>
                    <div class="card-body p-0 pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4" width="5%">#</th>
                                        <th width="35%">{{ __('marks.student_name') }}</th>
                                        {{-- Updated header to Admission No --}}
                                        <th width="20%">{{ __('marks.admission_no') ?? 'Admission No' }}</th>
                                        <th width="25%">{{ __('marks.marks_obtained') }} <span class="text-muted small" id="table_header_total"></span></th>
                                        <th class="text-center" width="15%">{{ __('marks.is_absent') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="student_table_body">
                                    {{-- Rows injected via JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-end pb-4 pe-4">
                        <button type="submit" class="btn btn-primary btn-lg shadow px-5">
                            <i class="fa fa-save me-2"></i> {{ __('marks.save_marks') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Empty State --}}
        <div id="empty_state" class="text-center my-5 d-none">
            <div class="alert alert-warning d-inline-block px-5">
                <i class="fa fa-exclamation-circle me-2"></i> No students found for the selected criteria.
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Helper to refresh NiceSelect/Select2/Selectpicker ---
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                // Conflict Fix: Ensure no other library is hogging the element
                if ($.fn.selectpicker && $(element).parent().hasClass('bootstrap-select')) {
                     try { $(element).selectpicker('destroy'); } catch(e){}
                     $(element).removeClass('selectpicker');
                }

                if($.fn.niceSelect) {
                    $(element).niceSelect('update');
                } else if ($.fn.select2) {
                     // Check if not initialized or update
                     if (!$(element).hasClass("select2-hidden-accessible")) {
                         $(element).select2();
                     } else {
                         $(element).trigger('change');
                     }
                }
            }
        }

        // --- SAFE INITIALIZATION (Fixes "Not Showing" Issue) ---
        setTimeout(function() {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.default-select').each(function() {
                    let $el = $(this);
                    // Destroy bootstrap-select if present to reveal the element for Select2
                    if ($.fn.selectpicker && ($el.data('selectpicker') || $el.parent().hasClass('bootstrap-select'))) {
                        try { $el.selectpicker('destroy'); } catch(e){}
                        $el.removeClass('selectpicker');
                    }
                    if (!$el.hasClass("select2-hidden-accessible")) {
                        $el.select2();
                    }
                });
            }
        }, 100);

        const examSelect = document.getElementById('exam_select');
        const classSelect = document.getElementById('class_select');
        const subjectSelect = document.getElementById('subject_select');
        
        const marksContainer = document.getElementById('marks_container');
        const emptyState = document.getElementById('empty_state');
        const loadingSpinner = document.getElementById('loading_spinner');
        
        // Total Marks Elements
        const totalMarksDisplay = document.getElementById('total_marks_display');
        const totalMarksValue = document.getElementById('total_marks_value');
        const tableHeaderTotal = document.getElementById('table_header_total');

        // --- 1. Exam Logic ---
        if (examSelect) {
            // If it's a SELECT element (Multiple Exams)
            if(examSelect.tagName === 'SELECT') {
                $(examSelect).on('change', function() { // Use jQuery on for Select2 compatibility
                    loadClasses(this.value);
                });
            } 
            // If it's HIDDEN (Single Exam)
            else if (examSelect.type === 'hidden' && examSelect.value) {
                loadClasses(examSelect.value);
            }
        }

        function loadClasses(examId) {
            // Reset dependent dropdowns
            resetSelect(classSelect, '-- {{ __('marks.select_class') }} --');
            resetSelect(subjectSelect, '-- Select Class First --');
            hideTable();

            if (!examId) return;
            
            const url = "{{ route('marks.get_classes') }}?exam_id=" + examId;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Clear and Populate
                    classSelect.innerHTML = '<option value="">-- {{ __("marks.select_class") }} --</option>';
                    
                    Object.entries(data).forEach(([id, name]) => {
                        let option = new Option(name, id);
                        classSelect.add(option);
                    });

                    classSelect.disabled = false;
                    refreshSelect(classSelect);
                })
                .catch(error => {
                    console.error('Error loading classes:', error);
                    classSelect.innerHTML = '<option value="">Error loading classes</option>';
                    refreshSelect(classSelect);
                });
        }

        // --- 2. Class Logic ---
        if (classSelect) {
            $(classSelect).on('change', function() {
                loadSubjects(this.value);
            });
        }

        function loadSubjects(classId) {
            resetSelect(subjectSelect, '-- {{ __('marks.select_subject') }} --');
            hideTable();

            if (!classId) return;

            const url = "{{ route('marks.get_subjects') }}?class_section_id=" + classId;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">-- {{ __("marks.select_subject") }} --</option>';
                    
                    // Handle Array of Objects [{id, name, total_marks}]
                    if (Array.isArray(data)) {
                        data.forEach(subject => {
                            let option = new Option(subject.name, subject.id);
                            // Store total marks in data attribute
                            option.setAttribute('data-total', subject.total_marks); 
                            subjectSelect.add(option);
                        });
                    } else {
                        // Fallback for simple key-value object (backward compatibility)
                        Object.entries(data).forEach(([id, name]) => {
                             subjectSelect.add(new Option(name, id));
                        });
                    }

                    subjectSelect.disabled = false;
                    refreshSelect(subjectSelect);
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                    refreshSelect(subjectSelect);
                });
        }

        // --- 3. Subject Logic & Total Marks ---
        if (subjectSelect) {
            $(subjectSelect).on('change', function() {
                const subjectId = this.value;
                const examId = $(examSelect).val(); 
                const classId = $(classSelect).val();

                // Show Total Marks Logic
                const selectedOption = this.options[this.selectedIndex];
                const totalMarks = selectedOption ? selectedOption.getAttribute('data-total') : null;
                
                if (totalMarks) {
                    totalMarksValue.textContent = totalMarks;
                    totalMarksDisplay.classList.remove('d-none');
                    if(tableHeaderTotal) tableHeaderTotal.textContent = `(Max: ${totalMarks})`;
                } else {
                    totalMarksDisplay.classList.add('d-none');
                    if(tableHeaderTotal) tableHeaderTotal.textContent = '';
                }

                if (examId && classId && subjectId) {
                    loadStudents(examId, classId, subjectId);
                } else {
                    hideTable();
                }
            });
        }

        function loadStudents(examId, classId, subjectId) {
            loadingSpinner.classList.remove('d-none');
            marksContainer.classList.add('d-none');
            emptyState.classList.add('d-none');

            // Set Form Inputs
            document.getElementById('form_exam_id').value = examId;
            document.getElementById('form_class_id').value = classId;
            document.getElementById('form_subject_id').value = subjectId;

            // Using jQuery for the AJAX call to load the table content
            $.ajax({
                url: "{{ route('marks.get_students') }}",
                type: "GET",
                data: {
                    exam_id: examId,
                    class_section_id: classId,
                    subject_id: subjectId
                },
                dataType: 'json',
                success: function(response) {
                    loadingSpinner.classList.add('d-none');
                    const students = response.students;
                    const marks = response.marks;

                    if (students.length > 0) {
                        let rows = '';
                        
                        // Get current Total Marks Limit
                        const maxMarks = totalMarksValue.textContent || 100; // Fallback to 100

                        students.forEach((student, index) => {
                            let markData = marks[student.id] || { marks_obtained: '', is_absent: 0 };
                            let isAbsentChecked = markData.is_absent ? 'checked' : '';
                            let isInputDisabled = markData.is_absent ? 'disabled' : '';
                            let markValue = markData.is_absent ? '' : markData.marks_obtained;

                            rows += `
                                <tr>
                                    <td class="ps-4 fw-bold">${index + 1}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="w-space-no fw-bold text-dark">${student.name}</span>
                                        </div>
                                    </td>
                                    <!-- Updated JS to use admission_number -->
                                    <td><span class="badge badge-light badge-sm text-dark">${student.admission_number}</span></td>
                                    <td>
                                        <input type="number" 
                                               name="marks[${student.id}]" 
                                               class="form-control mark-input w-75 border-secondary" 
                                               value="${markValue}" 
                                               min="0" max="${maxMarks}" step="0.01" 
                                               placeholder="Max: ${maxMarks}"
                                               ${isInputDisabled}>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-inline-block">
                                            <input class="form-check-input absent-check" 
                                                   type="checkbox" 
                                                   name="absent[${student.id}]" 
                                                   value="1"
                                                   ${isAbsentChecked}>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });

                        document.getElementById('student_table_body').innerHTML = rows;
                        document.getElementById('student_count_badge').innerText = students.length + ' Students';
                        marksContainer.classList.remove('d-none');
                        
                        bindAbsentLogic();
                        bindMarkValidation(maxMarks); // Apply instant validation
                    } else {
                        emptyState.classList.remove('d-none');
                    }
                },
                error: function(xhr) {
                    loadingSpinner.classList.add('d-none');
                    let msg = "Unauthorized or Error fetching data.";
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        }

        // --- Helper Utilities ---
        function resetSelect(selectElement, placeholder) {
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            selectElement.disabled = true;
            refreshSelect(selectElement);
            totalMarksDisplay.classList.add('d-none');
        }

        function hideTable() {
            marksContainer.classList.add('d-none');
            emptyState.classList.add('d-none');
        }

        function bindAbsentLogic() {
            $('.absent-check').off('change').on('change', function(){
                let row = $(this).closest('tr');
                let input = row.find('.mark-input');
                if($(this).is(':checked')) {
                    input.prop('disabled', true).val(''); 
                    input.removeClass('is-invalid'); // Clear errors if marked absent
                } else {
                    input.prop('disabled', false).focus();
                }
            });
        }

        // New: Validate marks as you type
        function bindMarkValidation(maxMarks) {
            $('.mark-input').off('input').on('input', function() {
                let val = parseFloat($(this).val());
                let max = parseFloat(maxMarks);
                
                if (val > max) {
                    $(this).addClass('is-invalid');
                    // Optional: Show tooltip or small text, but class red border is usually enough
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
        }

        // --- Form Submission ---
        $('#marksForm').submit(function(e){
            e.preventDefault();
            
            // 1. Client-Side Validation Check
            let maxMarks = parseFloat(totalMarksValue.textContent || 100);
            let hasError = false;
            
            $('.mark-input').each(function() {
                let val = $(this).val();
                if (val !== '' && parseFloat(val) > maxMarks) {
                    $(this).addClass('is-invalid');
                    hasError = true;
                }
            });

            if(hasError) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Marks',
                    text: `Marks cannot exceed total marks (${maxMarks}). Please correct the highlighted fields.`
                });
                return; // Stop submission
            }

            let btn = $(this).find('button[type="submit"]');
            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    btn.prop('disabled', false).html(originalText);
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function(xhr){
                    btn.prop('disabled', false).html(originalText);
                    let msg = 'Error occurred';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', html: msg });
                }
            });
        });

    });
</script>
@endsection