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
                            <div class="col-md-4 mb-3">
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

                            {{-- 2. Class Section (Combined Grade & Section) --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">{{ __('marks.select_section') }} <span class="text-danger">*</span></label>
                                <select id="section_select" class="form-control default-select">
                                    <option value="">-- {{ __('marks.select_section') }} --</option>
                                    @foreach($classes as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 3. Subject --}}
                            <div class="col-md-4 mb-3">
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
    document.addEventListener('DOMContentLoaded', function() {
        
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if ($.fn.selectpicker) $(element).selectpicker('refresh');
            }
        }

        const examSelect = document.getElementById('exam_select');
        const sectionSelect = document.getElementById('section_select');
        const subjectSelect = document.getElementById('subject_select');
        
        // Helper to clear and disable dropdowns
        function resetSelect(select, defaultText) {
            select.innerHTML = `<option value="">${defaultText}</option>`;
            select.disabled = true;
            refreshSelect(select);
        }

        // --- Helper: Fetch Subjects ---
        function fetchSubjects(sectionId) {
            resetSelect(subjectSelect, 'Loading...');
            
            // Pass class_section_id to backend, which will derive grade level and filter
            let url = "{{ route('marks.get_subjects') }}?class_section_id=" + sectionId;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">-- {{ __("marks.select_subject") }} --</option>';
                    data.forEach(s => {
                        let opt = new Option(s.name, s.id);
                        opt.dataset.total = s.total_marks;
                        opt.dataset.teacher = s.teacher_name;
                        subjectSelect.add(opt);
                    });
                    subjectSelect.disabled = false;
                    refreshSelect(subjectSelect);
                })
                .catch(err => {
                    console.error(err);
                    resetSelect(subjectSelect, '-- {{ __("marks.select_subject") }} --');
                });
        }

        // --- Step 1: Section Change (Now includes Grade info) ---
        if(sectionSelect) {
            sectionSelect.addEventListener('change', function() {
                let id = this.value;
                resetSelect(subjectSelect, '-- {{ __("marks.select_subject") }} --');
                
                document.getElementById('marks_container').classList.add('d-none');
                document.getElementById('empty_state').classList.add('d-none');

                if(!id) return;

                // Load Subjects based on Section
                fetchSubjects(id);
            });
        }

        // --- Step 2: Load Students ---
        if(subjectSelect) {
            subjectSelect.addEventListener('change', tryLoadStudents);
        }

        function tryLoadStudents() {
            let subId = subjectSelect.value;
            let secId = sectionSelect.value;
            let exId = examSelect.value;
            
            if(!subId || !secId || !exId) { 
                document.getElementById('marks_container').classList.add('d-none'); 
                return; 
            }

            let subjOpt = subjectSelect.options[subjectSelect.selectedIndex];
            let totalMarks = subjOpt.dataset.total || 100;
            let teacherName = subjOpt.dataset.teacher || 'N/A';

            document.getElementById('total_marks_value').innerText = totalMarks;
            document.getElementById('total_marks_display').classList.remove('d-none');
            document.getElementById('table_max_label').innerText = '(Max: ' + totalMarks + ')';
            
            document.getElementById('header_section').innerText = sectionSelect.options[sectionSelect.selectedIndex].text;
            document.getElementById('header_teacher').innerText = teacherName;

            document.getElementById('form_exam_id').value = exId; 
            document.getElementById('form_section_id').value = secId; 
            document.getElementById('form_subject_id').value = subId;

            document.getElementById('loading_spinner').classList.remove('d-none'); 
            document.getElementById('marks_container').classList.add('d-none'); 
            document.getElementById('empty_state').classList.add('d-none');

            let url = "{{ route('marks.get_students') }}?exam_id=" + exId + "&class_section_id=" + secId + "&subject_id=" + subId;

            fetch(url)
                .then(res => res.json())
                .then(res => {
                    document.getElementById('loading_spinner').classList.add('d-none');
                    if (res.students && res.students.length > 0) {
                        let rows = '';
                        document.getElementById('total_students_count').innerText = res.students.length;

                        res.students.forEach((s, i) => {
                            let m = res.marks[s.id] || {marks_obtained: '', is_absent: 0};
                            let absentChecked = m.is_absent ? 'checked' : '';
                            let inputDisabled = m.is_absent ? 'disabled' : '';
                            let markValue = m.is_absent ? '' : m.marks_obtained;

                            rows += `<tr class="s-row">
                                <td class="ps-4 fw-bold">${i+1}</td>
                                <td><span class="s-name fw-bold text-dark">${s.name}</span></td>
                                <td><span class="s-adm badge badge-light badge-sm text-dark">${s.admission_number}</span></td>
                                <td><input type="number" name="marks[${s.id}]" class="form-control mark-input w-50 border-secondary" value="${markValue}" ${inputDisabled} max="${totalMarks}" step="0.01" placeholder="0-${totalMarks}"></td>
                                <td class="text-center"><div class="form-check d-inline-block"><input type="checkbox" name="absent[${s.id}]" class="form-check-input abs-check" ${absentChecked}></div></td>
                            </tr>`;
                        });
                        document.getElementById('student_table_body').innerHTML = rows; 
                        document.getElementById('marks_container').classList.remove('d-none');
                    } else {
                        document.getElementById('empty_state').classList.remove('d-none');
                    }
                })
                .catch(err => {
                    document.getElementById('loading_spinner').classList.add('d-none');
                    Swal.fire({icon: 'error', title: 'Error', text: 'Failed to load data.'});
                });
        }

        // --- Logic: Print Award List ---
        const printBtn = document.getElementById('print_award_list');
        if(printBtn) {
            printBtn.addEventListener('click', function() {
                let examId = examSelect.value;
                let classId = sectionSelect.value;
                let subjectId = subjectSelect.value;

                if (!examId || !classId || !subjectId) {
                    Swal.fire({icon: 'warning', title: 'Incomplete Selection', text: 'Please select Exam, Section, and Subject first.'});
                    return;
                }

                let url = "{{ route('exams.print_award_list') }}?exam_id=" + examId + "&class_section_id=" + classId + "&subject_id=" + subjectId;
                window.open(url, '_blank');
            });
        }

        // --- Logic: Event Delegation for Table Inputs ---
        const tableBody = document.getElementById('student_table_body');
        
        // Absent Toggle
        tableBody.addEventListener('change', function(e) {
            if(e.target.classList.contains('abs-check')) {
                let input = e.target.closest('tr').querySelector('.mark-input');
                if(e.target.checked) { 
                    input.disabled = true; 
                    input.value = ''; 
                    input.classList.remove('is-invalid'); 
                } else { 
                    input.disabled = false; 
                    input.focus(); 
                }
            }
        });

        // Validation on Input
        tableBody.addEventListener('input', function(e) {
            if(e.target.classList.contains('mark-input')) {
                let max = parseFloat(document.getElementById('total_marks_value').innerText) || 100;
                if(parseFloat(e.target.value) > max) {
                    e.target.classList.add('is-invalid');
                } else {
                    e.target.classList.remove('is-invalid');
                }
            }
        });

        // --- Logic: Search ---
        const searchInput = document.getElementById('table_search');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                let v = this.value.toLowerCase();
                document.querySelectorAll('.s-row').forEach(row => {
                    let name = row.querySelector('.s-name').innerText.toLowerCase();
                    let adm = row.querySelector('.s-adm').innerText.toLowerCase();
                    row.style.display = (name.includes(v) || adm.includes(v)) ? '' : 'none';
                });
            });
        }

        // --- Logic: Save (Updated: Empty Mark Validation) ---
        const marksForm = document.getElementById('marksForm');
        if(marksForm) {
            marksForm.addEventListener('submit', function(e) {
                e.preventDefault();
                let max = parseFloat(document.getElementById('total_marks_value').innerText) || 100;
                let err = false;
                let emptyErr = false;

                document.querySelectorAll('.mark-input').forEach(input => { 
                    let val = input.value;
                    let isAbsent = input.closest('tr').querySelector('.abs-check').checked;

                    // Check Max
                    if(parseFloat(val) > max) { 
                        input.classList.add('is-invalid'); 
                        err = true; 
                    } 
                    
                    // Check Empty (unless absent)
                    if(!isAbsent && (val === '' || val === null)) {
                        input.classList.add('is-invalid');
                        emptyErr = true;
                    } else if(!err) { // Only remove if no max error
                        input.classList.remove('is-invalid');
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

                let btn = this.querySelector('button[type="submit"]'); 
                let txt = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
                
                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false; 
                    btn.innerHTML = txt;
                    Swal.fire({
                        icon: 'success', 
                        title: 'Success!', 
                        text: data.message, 
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    });
                })
                .catch(err => { 
                    btn.disabled = false; 
                    btn.innerHTML = txt; 
                    Swal.fire('Error', 'An error occurred while saving marks.', 'error'); 
                });
            });
        }
    });
</script>
@endsection