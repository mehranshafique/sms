@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('invoice.generate_invoices') }}</h4>
                    <p class="mb-0">{{ __('invoice.generate_subtitle') }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
            @csrf
            
            {{-- 1. Configuration Section (Top Row) --}}
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('invoice.configuration') }}</h4>
                </div>
                <div class="card-body pt-2">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('invoice.target_grade') }} <span class="text-danger">*</span></label>
                            <select name="grade_id" id="gradeSelect" class="form-control default-select" data-live-search="true" required>
                                <option value="">{{ __('invoice.select_grade') }}</option>
                                @foreach($grades as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('invoice.target_section') }} <span class="text-danger">*</span></label>
                            <select name="class_section_id" id="sectionSelect" class="form-control default-select" required disabled>
                                <option value="">{{ __('invoice.select_grade_first') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('invoice.issue_date') }}</label>
                            <input type="text" name="issue_date" class="form-control datepicker" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('invoice.due_date') }}</label>
                            <input type="text" name="due_date" class="form-control datepicker" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Selection Columns (Students & Fees) --}}
            <div class="row">
                
                {{-- Left Column: Students --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                            <h4 class="card-title">{{ __('invoice.select_students') }} <span class="text-danger">*</span></h4>
                            <div class="form-check custom-checkbox">
                                <input type="checkbox" class="form-check-input" id="selectAllStudents" disabled>
                                <label class="form-check-label" for="selectAllStudents">{{ __('invoice.select_all') }}</label>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Optional Student Search --}}
                            <input type="text" id="studentSearch" class="form-control mb-3 input-sm" placeholder="{{ __('invoice.search_student') }}" disabled>
                            
                            <div class="border rounded p-3" style="height: 300px; overflow-y: auto; background: #f8f9fa;">
                                <div id="studentList" class="row">
                                    <div class="col-12 text-center text-muted mt-5 pt-5">
                                        <i class="fa fa-users fa-2x mb-2"></i><br>
                                        {{ __('invoice.select_class_first_msg') }}
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block" id="studentCount">{{ __('invoice.students_selected_count', ['count' => 0]) }}</small>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Fees --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header border-0 pb-0">
                            <h4 class="card-title">{{ __('invoice.select_fees') }} <span class="text-danger">*</span></h4>
                        </div>
                        <div class="card-body">
                            {{-- Fee Search --}}
                            <input type="text" id="feeSearch" class="form-control mb-3 input-sm" placeholder="{{ __('invoice.search_fees') }}" disabled>

                            <div class="border rounded p-3" style="height: 300px; overflow-y: auto; background: #f8f9fa;">
                                <div id="feeList" class="d-flex flex-column">
                                    <div class="text-center text-muted mt-5 pt-5">
                                        <i class="fa fa-money fa-2x mb-2"></i><br>
                                        {{ __('invoice.fees_will_load') }}
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">{{ __('invoice.fee_bundle_help') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Generate Action --}}
            <div class="row mt-4 mb-4">
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow" id="generateBtn" disabled>
                        <i class="fa fa-paper-plane me-2"></i> {{ __('invoice.generate_btn') }}
                    </button>
                </div>
            </div>

        </form>

        {{-- 3. Fee Reference Table (Below Button) --}}
        <div class="card d-none" id="feeReferenceSection">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fa fa-table me-2"></i> {{ __('invoice.class_fee_overview') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-primary">
                            <tr>
                                <th>{{ __('invoice.fee_name') }}</th>
                                <th>{{ __('invoice.fee_type') }}</th>
                                <th>{{ __('invoice.amount') }}</th>
                                <th>{{ __('invoice.mode') }}</th>
                                <th>{{ __('invoice.order') }}</th>
                                <th>{{ __('invoice.frequency') }}</th>
                            </tr>
                        </thead>
                        <tbody id="feeReferenceTableBody">
                            {{-- Populated via AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- HELPER: Refresh UI Library (Bootstrap-Select) ---
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select') && $.fn.selectpicker) {
                 $(element).selectpicker('refresh');
            }
        }

        // --- DOM Elements ---
        const gradeSelect = document.getElementById('gradeSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        
        const studentList = document.getElementById('studentList');
        const feeList = document.getElementById('feeList');
        const feeReferenceSection = document.getElementById('feeReferenceSection');
        const feeReferenceTableBody = document.getElementById('feeReferenceTableBody');
        
        const studentSearch = document.getElementById('studentSearch');
        const feeSearch = document.getElementById('feeSearch');
        const selectAllStudents = document.getElementById('selectAllStudents');
        
        const generateBtn = document.getElementById('generateBtn');
        const studentCount = document.getElementById('studentCount');
        const invoiceForm = document.getElementById('invoiceForm');

        // --- Helper: Reset UI Lists ---
        function resetLists() {
            studentList.innerHTML = '<div class="col-12 text-center text-muted mt-5 pt-5"><i class="fa fa-users fa-2x mb-2"></i><br>{{ __("invoice.select_class_first_msg") }}</div>';
            feeList.innerHTML = '<div class="text-center text-muted mt-5 pt-5"><i class="fa fa-money fa-2x mb-2"></i><br>{{ __("invoice.select_class_first_msg") }}</div>';
            
            [studentSearch, feeSearch, selectAllStudents].forEach(el => {
                el.disabled = true;
                if(el.type === 'checkbox') el.checked = false;
                else el.value = '';
            });

            feeReferenceSection.classList.add('d-none');
            feeReferenceTableBody.innerHTML = '';
            generateBtn.disabled = true;
            studentCount.innerText = '{{ __("invoice.students_selected_count", ["count" => 0]) }}';
        }

        function updateStudentCount() {
            const count = document.querySelectorAll('.student-check:checked').length;
            studentCount.innerText = count + ' {{ __("invoice.students_selected_suffix") }}';
        }

        // --- 1. Logic: Grade -> Section ---
        if (gradeSelect) {
            gradeSelect.addEventListener('change', function() {
                const gradeId = this.value;
                
                // Reset Section UI
                sectionSelect.innerHTML = '<option value="">{{ __("invoice.loading") }}</option>';
                sectionSelect.disabled = true;
                refreshSelect(sectionSelect);
                
                // Reset lower lists
                resetLists();

                if (gradeId) {
                    fetch(`{{ route('invoices.get_sections') }}?grade_id=${gradeId}`)
                        .then(response => response.json())
                        .then(data => {
                            sectionSelect.innerHTML = '<option value="">{{ __("invoice.select_section") }}</option>';
                            
                            // Iterate object {id: name}
                            Object.entries(data).forEach(([key, value]) => {
                                let option = new Option(value, key);
                                sectionSelect.add(option);
                            });

                            if (sectionSelect.options.length > 1) {
                                sectionSelect.disabled = false;
                            } else {
                                sectionSelect.innerHTML = '<option value="">{{ __("invoice.no_sections_found") }}</option>';
                            }
                            refreshSelect(sectionSelect);
                        })
                        .catch(err => {
                            console.error(err);
                            sectionSelect.innerHTML = '<option value="">{{ __("invoice.error_loading") }}</option>';
                            refreshSelect(sectionSelect);
                        });
                } else {
                    sectionSelect.innerHTML = '<option value="">{{ __("invoice.select_grade_first") }}</option>';
                    refreshSelect(sectionSelect);
                }
            });
        }

        // --- 2. Logic: Section -> Students & Fees ---
        if (sectionSelect) {
            sectionSelect.addEventListener('change', function() {
                const sectionId = this.value;
                resetLists();

                if (sectionId) {
                    // Enable inputs
                    [studentSearch, feeSearch, selectAllStudents].forEach(el => el.disabled = false);
                    
                    // Show Loaders
                    const loader = '<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i> {{ __("invoice.loading") }}</div>';
                    studentList.innerHTML = loader;
                    feeList.innerHTML = loader;

                    // A. Fetch Students
                    fetch(`{{ route('invoices.get_students') }}?class_section_id=${sectionId}`)
                        .then(res => res.json())
                        .then(data => {
                            studentList.innerHTML = '';
                            if (data.length > 0) {
                                let html = '';
                                data.forEach(student => {
                                    html += `
                                        <div class="col-12 student-item">
                                            <div class="form-check custom-checkbox mb-2">
                                                <input type="checkbox" name="student_ids[]" value="${student.id}" class="form-check-input student-check" id="student_${student.id}" checked>
                                                <label class="form-check-label w-100" for="student_${student.id}">
                                                    <span class="fw-bold">${student.name}</span> 
                                                    <span class="float-end text-muted small">${student.admission_number}</span>
                                                </label>
                                            </div>
                                        </div>`;
                                });
                                studentList.innerHTML = html;
                                selectAllStudents.checked = true;
                                updateStudentCount();
                                generateBtn.disabled = false;
                            } else {
                                studentList.innerHTML = '<div class="col-12 text-warning text-center p-4">{{ __("invoice.no_active_students") }}</div>';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            studentList.innerHTML = '<div class="text-danger text-center">{{ __("invoice.error_loading_students") }}</div>';
                        });

                    // B. Fetch Fees
                    fetch(`/finance/invoices/get-fees?class_section_id=${sectionId}`)
                        .then(res => res.json())
                        .then(data => {
                            feeList.innerHTML = '';
                            feeReferenceTableBody.innerHTML = '';

                            if (data.length > 0) {
                                let listHtml = '';
                                let tableHtml = '';

                                data.forEach(fee => {
                                    // List Item
                                    listHtml += `
                                        <div class="fee-item border-bottom pb-2 mb-2">
                                            <div class="form-check custom-checkbox">
                                                <input type="checkbox" name="fee_structure_ids[]" value="${fee.id}" class="form-check-input fee-check" id="fee_${fee.id}">
                                                <label class="form-check-label w-100" for="fee_${fee.id}">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="fee-name fw-bold">${fee.name}</span>
                                                        <span class="badge badge-primary badge-sm">${fee.amount}</span>
                                                    </div>
                                                    <div class="small text-muted">
                                                        ${fee.type} | ${fee.payment_mode} ${fee.order !== '-' ? '#'+fee.order : ''}
                                                    </div>
                                                </label>
                                            </div>
                                        </div>`;

                                    // Table Row
                                    tableHtml += `
                                        <tr>
                                            <td><strong>${fee.name}</strong></td>
                                            <td>${fee.type}</td>
                                            <td>${fee.amount}</td>
                                            <td><span class="badge badge-light text-dark">${fee.payment_mode}</span></td>
                                            <td>${fee.order}</td>
                                            <td>${fee.frequency}</td>
                                        </tr>`;
                                });

                                feeList.innerHTML = listHtml;
                                feeReferenceTableBody.innerHTML = tableHtml;
                                feeReferenceSection.classList.remove('d-none');
                            } else {
                                feeList.innerHTML = '<div class="col-12 text-danger text-center p-4">{{ __("invoice.no_fees_found") }}</div>';
                                feeReferenceSection.classList.add('d-none');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            feeList.innerHTML = '<div class="text-danger text-center">{{ __("invoice.error_loading_fees") }}</div>';
                        });
                }
            });
        }

        // --- 3. Search Logic ---
        studentSearch.addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            document.querySelectorAll('#studentList .student-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });

        feeSearch.addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            document.querySelectorAll('#feeList .fee-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });

        // --- 4. Select All Logic ---
        selectAllStudents.addEventListener('change', function() {
            const isChecked = this.checked;
            // Only toggle visible items (in case search is active)
            const visibleItems = Array.from(document.querySelectorAll('#studentList .student-item')).filter(el => el.style.display !== 'none');
            
            visibleItems.forEach(item => {
                const checkbox = item.querySelector('.student-check');
                if(checkbox) checkbox.checked = isChecked;
            });
            updateStudentCount();
        });

        // Event Delegation for dynamic checkboxes
        document.addEventListener('change', function(e) {
            if(e.target && e.target.classList.contains('student-check')) {
                updateStudentCount();
                
                // Update "Select All" status
                const allVisible = Array.from(document.querySelectorAll('#studentList .student-item')).filter(el => el.style.display !== 'none');
                const allVisibleChecks = allVisible.map(item => item.querySelector('.student-check'));
                // Check if ALL are checked
                if (allVisibleChecks.length > 0) {
                    const allChecked = allVisibleChecks.every(cb => cb.checked);
                    selectAllStudents.checked = allChecked;
                }
            }
        });

        // --- 5. Submit Logic (Native JS) ---
        invoiceForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const studentChecked = document.querySelectorAll('.student-check:checked').length;
            const feeChecked = document.querySelectorAll('.fee-check:checked').length;

            if (studentChecked === 0) {
                Swal.fire({ icon: 'warning', title: '{{ __("invoice.warning") }}', text: '{{ __("invoice.select_student_warning") }}' });
                return;
            }
            if (feeChecked === 0) {
                Swal.fire({ icon: 'warning', title: '{{ __("invoice.warning") }}', text: '{{ __("invoice.select_fee_warning") }}' });
                return;
            }

            const btn = generateBtn;
            const originalText = btn.innerHTML;
            const formData = new FormData(this);

            // Inner function to perform the actual submit
            const performSubmit = () => {
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __("invoice.processing") }}';
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json().then(data => ({ status: res.status, body: data })))
                .then(res => {
                    if (res.status >= 200 && res.status < 300) {
                        Swal.fire({ icon: 'success', title: '{{ __("invoice.success") }}', text: res.body.message })
                            .then(() => {
                                if(res.body.redirect) window.location.href = res.body.redirect;
                            });
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        let msg = res.body.message || "{{ __('invoice.error_occurred') }}";
                        Swal.fire({ icon: 'error', title: '{{ __("invoice.error") }}', html: msg });
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    console.error(err);
                    Swal.fire({ icon: 'error', title: '{{ __("invoice.error") }}', text: '{{ __("invoice.unexpected_error") }}' });
                });
            };

            // 1. Check for duplicates first
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __("invoice.checking") }}';

            const params = new URLSearchParams(formData).toString();

            fetch(`/finance/invoices/check-duplicates?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.has_duplicates) {
                    Swal.fire({
                        title: '{{ __("invoice.duplicate_warning_title") }}',
                        text: data.message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '{{ __("invoice.yes_generate") }}'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performSubmit();
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }
                    });
                } else {
                    performSubmit();
                }
            })
            .catch(err => {
                console.error('Check Error', err);
                // Fallback: Try submitting anyway if check fails
                performSubmit();
            });
        });
    });
</script>
@endsection