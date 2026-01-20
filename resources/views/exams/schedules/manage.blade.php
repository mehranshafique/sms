@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Header --}}
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('exam_schedule.manage_title') }}</h4>
                    <p class="mb-0">{{ __('exam_schedule.subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        {{-- Form 1: Load Data / Download Admit Cards --}}
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('exam_schedule.select_exam') }} <span class="text-danger">*</span></label>
                                <select id="exam_id" class="form-control default-select">
                                    <option value="">-- {{ __('exam_schedule.select_exam') }} --</option>
                                    @foreach($exams as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('exam_schedule.select_class') }} <span class="text-danger">*</span></label>
                                <select id="class_section_id" class="form-control default-select">
                                    <option value="">-- {{ __('exam_schedule.select_class') }} --</option>
                                    @foreach($classes as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Student Selector --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('student.student_details') }} ({{ __('class_section.optional') }})</label>
                                <select id="student_id" class="form-control default-select" data-live-search="true" disabled>
                                    <option value="">-- {{ __('invoice.select_all') }} --</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-flex gap-2">
                                <button id="loadBtn" class="btn btn-primary flex-grow-1">
                                    <i class="fa fa-search me-2"></i> {{ __('exam_schedule.load_subjects') }}
                                </button>
                                
                                {{-- Admit Card Download Form --}}
                                <form action="{{ route('exam-schedules.download-admit-cards') }}" method="POST" target="_blank" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="exam_id" id="print_exam_id">
                                    <input type="hidden" name="class_section_id" id="print_class_id">
                                    <input type="hidden" name="student_id" id="print_student_id">
                                    <button type="submit" id="printBtn" class="btn btn-info" disabled>
                                        <i class="fa fa-print"></i> {{ __('exam_schedule.download_admit_card') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule Form (Hidden initially) --}}
        <div class="row d-none" id="scheduleContainer">
            <div class="col-12">
                <form id="scheduleForm" action="{{ route('exam-schedules.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="exam_id" id="hidden_exam_id">
                    <input type="hidden" name="class_section_id" id="hidden_class_id">

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">{{ __('exam_schedule.manage_title') }}</h4>
                                <small class="text-muted d-block mt-1" id="dateRangeHint"></small>
                            </div>
                            {{-- Auto Fill Button --}}
                            <button type="button" id="autoFillBtn" class="btn btn-warning btn-sm text-white">
                                <i class="fa fa-magic me-1"></i> {{ __('exam_schedule.auto_fill') }}
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered verticle-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>{{ __('exam_schedule.subject') }}</th>
                                            <th width="20%">{{ __('exam_schedule.date') }} <span class="text-danger">*</span></th>
                                            <th width="15%">{{ __('exam_schedule.start_time') }} <span class="text-danger">*</span></th>
                                            <th width="15%">{{ __('exam_schedule.end_time') }} <span class="text-danger">*</span></th>
                                            <th>{{ __('exam_schedule.room') }}</th>
                                            <th width="10%">{{ __('exam_schedule.max_marks') }}</th>
                                            <th width="10%">{{ __('exam_schedule.pass_marks') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scheduleTableBody">
                                        {{-- Rows loaded via JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-save me-2"></i> {{ __('exam_schedule.save_schedule') }}
                            </button>
                        </div>
                    </div>
                </form>
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

        // Initialize Date/Time Pickers Helper Function
        function initPickers() {
            // Initialize Datepicker
            if (jQuery().bootstrapMaterialDatePicker) {
                jQuery('.datepicker-default').bootstrapMaterialDatePicker({
                    weekStart: 0,
                    time: false,
                    format: 'YYYY-MM-DD'
                });
            }
            
            // Initialize Clock Picker
            if(jQuery().clockpicker) {
                 jQuery('.timepicker').clockpicker({
                    placement: 'bottom',
                    align: 'left',
                    donetext: 'Done',
                    autoclose: true
                });
            }
        }

        const examSelect = document.getElementById('exam_id');
        const classSelect = document.getElementById('class_section_id');
        const studentSelect = document.getElementById('student_id');
        const loadBtn = document.getElementById('loadBtn');
        const autoFillBtn = document.getElementById('autoFillBtn');
        const scheduleForm = document.getElementById('scheduleForm');
        
        // 1. Sync Print Hidden Fields
        function syncPrintFields() {
            document.getElementById('print_exam_id').value = examSelect.value;
            document.getElementById('print_class_id').value = classSelect.value;
            document.getElementById('print_student_id').value = studentSelect.value;
            
            // Enable print button only if Exam and Class are selected
            document.getElementById('printBtn').disabled = !(examSelect.value && classSelect.value);
        }

        if(examSelect) examSelect.addEventListener('change', syncPrintFields);
        if(studentSelect) studentSelect.addEventListener('change', syncPrintFields);

        // 2. When Class changes, fetch Students using Fetch API
        if(classSelect) {
            classSelect.addEventListener('change', function() {
                const classId = this.value;
                
                // Reset student dropdown
                studentSelect.innerHTML = '<option value="">-- {{ __("invoice.select_all") }} --</option>';
                studentSelect.disabled = true;
                refreshSelect(studentSelect);

                if(classId) {
                    fetch("{{ route('exam-schedules.get-students') }}?class_section_id=" + classId)
                        .then(response => response.json())
                        .then(students => {
                            students.forEach(s => {
                                let option = new Option(s.text, s.id);
                                studentSelect.add(option);
                            });
                            studentSelect.disabled = false;
                            refreshSelect(studentSelect);
                        })
                        .catch(error => console.error('Error fetching students:', error));
                }
                
                syncPrintFields();
            });
        }

        // 3. Load Subjects
        if(loadBtn) {
            loadBtn.addEventListener('click', function() {
                const examId = examSelect.value;
                const classId = classSelect.value;

                if (!examId || !classId) {
                    Swal.fire('{{ __("invoice.warning") }}', "{{ __('exam_schedule.select_filters') }}", 'warning');
                    return;
                }

                // Set hidden inputs for save form
                document.getElementById('hidden_exam_id').value = examId;
                document.getElementById('hidden_class_id').value = classId;

                // Show loading
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __("invoice.loading") }}';
                this.disabled = true;

                const params = new URLSearchParams({ exam_id: examId, class_section_id: classId });

                fetch("{{ route('exam-schedules.get-subjects') }}?" + params.toString())
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok) throw data;
                        return data;
                    })
                    .then(response => {
                        document.getElementById('dateRangeHint').textContent = `{{ __('exam_schedule.date') }}: ${response.exam_start} - ${response.exam_end}`;
                        
                        const tbody = document.getElementById('scheduleTableBody');
                        tbody.innerHTML = '';

                        if(response.rows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" class="text-center">{{ __('exam_schedule.no_subjects_found') }}</td></tr>';
                        } else {
                            response.rows.forEach(row => {
                                const tr = document.createElement('tr');
                                // Use 'datepicker-default' class for dates and 'timepicker' class for times
                                tr.innerHTML = `
                                    <td>
                                        <strong>${row.subject_name}</strong>
                                        <input type="hidden" name="schedules[${row.subject_id}][id]" value="${row.id || ''}">
                                    </td>
                                    <td>
                                        <input type="text" name="schedules[${row.subject_id}][date]" 
                                            class="form-control datepicker-default exam-date-input" 
                                            placeholder="YYYY-MM-DD"
                                            value="${row.date}"
                                            data-subject-id="${row.subject_id}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="schedules[${row.subject_id}][start_time]" 
                                            class="form-control timepicker exam-start-input" 
                                            value="${row.start_time}" placeholder="09:00"
                                            data-subject-id="${row.subject_id}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="schedules[${row.subject_id}][end_time]" 
                                            class="form-control timepicker exam-end-input" 
                                            value="${row.end_time}" placeholder="12:00"
                                            data-subject-id="${row.subject_id}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="schedules[${row.subject_id}][room_number]" 
                                            class="form-control" placeholder="{{ __('exam_schedule.room') }}" value="${row.room_number}">
                                    </td>
                                    <td>
                                        <input type="number" name="schedules[${row.subject_id}][max_marks]" 
                                            class="form-control" value="${row.max_marks}">
                                    </td>
                                    <td>
                                        <input type="number" name="schedules[${row.subject_id}][pass_marks]" 
                                            class="form-control" value="${row.pass_marks}">
                                    </td>
                                `;
                                tbody.appendChild(tr);
                            });

                            // Re-initialize Date/Time Pickers on dynamic elements
                            initPickers();
                        }

                        document.getElementById('scheduleContainer').classList.remove('d-none');
                    })
                    .catch(error => {
                        let msg = error.message || '{{ __("invoice.error_loading") }}';
                        Swal.fire('{{ __("invoice.error") }}', msg, 'error');
                    })
                    .finally(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    });
            });
        }

        // 4. Auto Fill Button Handler
        if(autoFillBtn) {
            autoFillBtn.addEventListener('click', function() {
                const examId = examSelect.value;
                const classId = classSelect.value;

                if (!examId || !classId) {
                    Swal.fire('{{ __("invoice.warning") }}', "{{ __('exam_schedule.select_filters') }}", 'warning');
                    return;
                }

                Swal.fire({
                    title: '{{ __("exam_schedule.auto_fill") }}',
                    text: '{{ __("exam_schedule.auto_fill_confirm") }}',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("invoice.yes_generate") }}', // Reused key or specific one
                    cancelButtonText: '{{ __("finance.cancel") }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const originalText = autoFillBtn.innerHTML;
                        autoFillBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __("invoice.processing") }}';
                        autoFillBtn.disabled = true;

                        const params = new URLSearchParams({ exam_id: examId, class_section_id: classId });

                        fetch("{{ route('exam-schedules.auto-generate') }}?" + params.toString())
                            .then(async response => {
                                const data = await response.json();
                                if (!response.ok) throw data;
                                return data;
                            })
                            .then(data => {
                                const schedule = data.schedule;
                                Object.keys(schedule).forEach(subjectId => {
                                    const item = schedule[subjectId];
                                    
                                    const dateInput = document.querySelector(`.exam-date-input[data-subject-id="${subjectId}"]`);
                                    const startInput = document.querySelector(`.exam-start-input[data-subject-id="${subjectId}"]`);
                                    const endInput = document.querySelector(`.exam-end-input[data-subject-id="${subjectId}"]`);

                                    if(dateInput) {
                                        dateInput.value = item.date;
                                        // Update Material DatePicker if attached (re-set date)
                                        if (jQuery(dateInput).bootstrapMaterialDatePicker) {
                                            jQuery(dateInput).bootstrapMaterialDatePicker('setDate', item.date);
                                        }
                                    }
                                    if(startInput) startInput.value = item.start_time;
                                    if(endInput) endInput.value = item.end_time;
                                });

                                Swal.fire('{{ __("invoice.success") }}', data.message, 'success');
                            })
                            .catch(error => {
                                let msg = error.message || '{{ __("invoice.error_occurred") }}';
                                Swal.fire('{{ __("invoice.error") }}', msg, 'error');
                            })
                            .finally(() => {
                                autoFillBtn.innerHTML = originalText;
                                autoFillBtn.disabled = false;
                            });
                    }
                });
            });
        }

        // 5. Submit Form
        if(scheduleForm) {
            scheduleForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __("invoice.processing") }}';
                btn.disabled = true;

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw data;
                    return data;
                })
                .then(response => {
                    Swal.fire('{{ __("invoice.success") }}', response.message, 'success');
                })
                .catch(error => {
                    let msg = error.message || "{{ __('exam_schedule.validation_error') }}";
                    Swal.fire('{{ __("invoice.error") }}', msg, 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        }

    });
</script>
@endsection