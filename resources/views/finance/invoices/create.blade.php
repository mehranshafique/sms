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
                    <h4 class="card-title">1. Configuration</h4>
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
                            <h4 class="card-title">2. Select Students <span class="text-danger">*</span></h4>
                            <div class="form-check custom-checkbox">
                                <input type="checkbox" class="form-check-input" id="selectAllStudents" disabled>
                                <label class="form-check-label" for="selectAllStudents">Select All</label>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Optional Student Search --}}
                            <input type="text" id="studentSearch" class="form-control mb-3 input-sm" placeholder="Search student name..." disabled>
                            
                            <div class="border rounded p-3" style="height: 300px; overflow-y: auto; background: #f8f9fa;">
                                <div id="studentList" class="row">
                                    <div class="col-12 text-center text-muted mt-5 pt-5">
                                        <i class="fa fa-users fa-2x mb-2"></i><br>
                                        Please select a class section above.
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block" id="studentCount">0 students selected</small>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Fees --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header border-0 pb-0">
                            <h4 class="card-title">3. Select Fees <span class="text-danger">*</span></h4>
                        </div>
                        <div class="card-body">
                            {{-- Fee Search --}}
                            <input type="text" id="feeSearch" class="form-control mb-3 input-sm" placeholder="Search fees..." disabled>

                            <div class="border rounded p-3" style="height: 300px; overflow-y: auto; background: #f8f9fa;">
                                <div id="feeList" class="d-flex flex-column">
                                    <div class="text-center text-muted mt-5 pt-5">
                                        <i class="fa fa-money fa-2x mb-2"></i><br>
                                        Fees will load here.
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">Check multiple items to bundle fees.</small>
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
                <h5 class="card-title mb-0"><i class="fa fa-table me-2"></i> Class Fee Overview (Reference)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="thead-primary">
                            <tr>
                                <th>Fee Name</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Mode</th>
                                <th>Order</th>
                                <th>Frequency</th>
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
    $(document).ready(function(){
        
        if($.fn.selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

        // --- 1. Load Sections ---
        $('#gradeSelect').change(function(){
            let gradeId = $(this).val();
            let sectionSelect = $('#sectionSelect');
            
            // Reset
            sectionSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
            resetLists();
            
            if($.fn.selectpicker) sectionSelect.selectpicker('refresh');

            if(gradeId) {
                $.ajax({
                    url: "{{ route('invoices.get_sections') }}",
                    type: "GET",
                    data: { grade_id: gradeId },
                    success: function(data){
                        sectionSelect.empty();
                        if(Object.keys(data).length > 0){
                            sectionSelect.append('<option value="">{{ __("invoice.select_section") }}</option>');
                            $.each(data, function(key, value){
                                sectionSelect.append('<option value="'+key+'">'+value+'</option>');
                            });
                            sectionSelect.prop('disabled', false);
                        } else {
                            sectionSelect.append('<option value="">No sections found</option>');
                        }
                        if($.fn.selectpicker) setTimeout(() => sectionSelect.selectpicker('refresh'), 100);
                    }
                });
            } else {
                sectionSelect.empty().append('<option value="">{{ __("invoice.select_grade_first") }}</option>');
                if($.fn.selectpicker) sectionSelect.selectpicker('refresh');
            }
        });

        // --- 2. Load Students & Fees ---
        $('#sectionSelect').change(function(){
            let sectionId = $(this).val();
            resetLists();

            if(sectionId) {
                // Enable inputs
                $('#studentSearch, #feeSearch, #selectAllStudents').prop('disabled', false);
                $('#studentList').html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
                $('#feeList').html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');

                // A. Get Students
                $.ajax({
                    url: "{{ route('invoices.get_students') }}", 
                    type: "GET",
                    data: { class_section_id: sectionId },
                    success: function(data){
                        let list = $('#studentList');
                        list.empty();
                        if(data.length > 0){
                            $.each(data, function(index, student){
                                let item = `
                                    <div class="col-12 student-item">
                                        <div class="form-check custom-checkbox mb-2">
                                            <input type="checkbox" name="student_ids[]" value="${student.id}" class="form-check-input student-check" id="student_${student.id}" checked>
                                            <label class="form-check-label w-100" for="student_${student.id}">
                                                <span class="fw-bold">${student.name}</span> 
                                                <span class="float-end text-muted small">${student.admission_number}</span>
                                            </label>
                                        </div>
                                    </div>`;
                                list.append(item);
                            });
                            $('#selectAllStudents').prop('checked', true);
                            updateStudentCount();
                            $('#generateBtn').prop('disabled', false);
                        } else {
                            list.html('<div class="col-12 text-warning text-center p-4">No active students found in this class.</div>');
                        }
                    }
                });

                // B. Get Fees
                $.ajax({
                    url: "/finance/invoices/get-fees",
                    type: "GET",
                    data: { class_section_id: sectionId },
                    success: function(data){
                        let list = $('#feeList');
                        let tableBody = $('#feeReferenceTableBody');
                        list.empty();
                        tableBody.empty();

                        if(data.length > 0){
                            $.each(data, function(index, fee){
                                // 1. Checkbox List
                                let item = `
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
                                list.append(item);

                                // 2. Reference Table Row
                                let row = `
                                    <tr>
                                        <td><strong>${fee.name}</strong></td>
                                        <td>${fee.type}</td>
                                        <td>${fee.amount}</td>
                                        <td><span class="badge badge-light text-dark">${fee.payment_mode}</span></td>
                                        <td>${fee.order}</td>
                                        <td>${fee.frequency}</td>
                                    </tr>`;
                                tableBody.append(row);
                            });
                            
                            // Only show table if we have data
                            $('#feeReferenceSection').removeClass('d-none');
                        } else {
                            list.html('<div class="col-12 text-danger text-center p-4">No fee structures defined.</div>');
                            $('#feeReferenceSection').addClass('d-none'); // Ensure hidden if empty
                        }
                    }
                });
            }
        });

        // --- Helper Functions ---

        function resetLists() {
            $('#studentList').empty().html('<div class="text-center text-muted mt-5 pt-5">Select class first</div>');
            $('#feeList').empty().html('<div class="text-center text-muted mt-5 pt-5">Select class first</div>');
            $('#studentSearch, #feeSearch, #selectAllStudents').prop('disabled', true).val('');
            $('#selectAllStudents').prop('checked', false);
            $('#feeReferenceSection').addClass('d-none');
            $('#feeReferenceTableBody').empty();
            $('#generateBtn').prop('disabled', true);
            $('#studentCount').text('0 students selected');
        }

        function updateStudentCount() {
            let count = $('.student-check:checked').length;
            $('#studentCount').text(count + ' students selected');
        }

        // --- Search Logic ---
        
        // Student Search
        $('#studentSearch').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $("#studentList .student-item").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Fee Search
        $('#feeSearch').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $("#feeList .fee-item").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Select All Logic
        $('#selectAllStudents').change(function(){
            let isChecked = $(this).prop('checked');
            // Only affect visible items (in case search filter is active)
            $('.student-item:visible .student-check').prop('checked', isChecked);
            updateStudentCount();
        });

        $(document).on('change', '.student-check', function(){
            updateStudentCount();
            // Update "Select All" state
            let allVisible = $('.student-item:visible .student-check');
            let allChecked = $('.student-item:visible .student-check:checked');
            $('#selectAllStudents').prop('checked', allVisible.length === allChecked.length);
        });

        // --- Submit Logic (Same as before) ---
        $('#invoiceForm').submit(function(e){
            e.preventDefault();
            
            if($('.student-check:checked').length === 0){
                Swal.fire({ icon: 'warning', title: 'Warning', text: 'Please select at least one student.' });
                return;
            }
            if($('.fee-check:checked').length === 0){
                Swal.fire({ icon: 'warning', title: 'Warning', text: 'Please select at least one fee structure.' });
                return;
            }

            let form = this;
            let formData = $(this).serialize();
            let btn = $('#generateBtn');
            let originalText = btn.html();
            
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Checking...');

            $.ajax({
                url: "/finance/invoices/check-duplicates",
                type: "GET",
                data: formData,
                success: function(checkData) {
                    if (checkData.has_duplicates) {
                        Swal.fire({
                            title: 'Duplicate Warning',
                            text: checkData.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, generate anyway'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitInvoice(form, btn, originalText);
                            } else {
                                btn.prop('disabled', false).html(originalText);
                            }
                        });
                    } else {
                        submitInvoice(form, btn, originalText);
                    }
                },
                error: function() {
                    submitInvoice(form, btn, originalText);
                }
            });
        });

        function submitInvoice(form, btn, originalText) {
            btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            $.ajax({
                url: $(form).attr('action'),
                type: "POST",
                data: $(form).serialize(),
                success: function(response){
                    Swal.fire({ icon: 'success', title: 'Success', text: response.message }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    btn.prop('disabled', false).html(originalText);
                    let msg = xhr.responseJSON.message || "{{ __('invoice.error_occurred') }}";
                    Swal.fire({ icon: 'error', title: 'Error', html: msg });
                }
            });
        }
    });
</script>
@endsection