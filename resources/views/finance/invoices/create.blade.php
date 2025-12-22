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
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">{{ __('invoice.invoice_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('invoice.generate_invoices') }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('invoice.invoice_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                            @csrf
                            
                            {{-- Section 1: Academic & Financial Context --}}
                            <div class="row border-bottom pb-4 mb-4">
                                <h5 class="text-primary mb-3">1. Select Context</h5>
                                
                                {{-- Target Class --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('invoice.target_class') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" id="classSectionSelect" class="form-control default-select" data-live-search="true" required>
                                        <option value="">{{ __('invoice.select_class') }}</option>
                                        @foreach($classes as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Issue Date --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('invoice.issue_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="issue_date" class="form-control datepicker" value="{{ date('Y-m-d') }}" placeholder="YYYY-MM-DD" required>
                                </div>
                                
                                {{-- Due Date --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('invoice.due_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="due_date" class="form-control datepicker" value="{{ date('Y-m-d', strtotime('+30 days')) }}" placeholder="YYYY-MM-DD" required>
                                </div>

                                {{-- Fee Structures --}}
                                <div class="col-md-12 mt-2">
                                    <label class="form-label fw-bold">{{ __('invoice.select_fees') }} <span class="text-danger">*</span></label>
                                    <div class="border rounded p-3 bg-light">
                                        <div class="row">
                                            @if(count($feeStructures) > 0)
                                                @foreach($feeStructures as $id => $name)
                                                    <div class="col-md-4 mb-2">
                                                        <div class="form-check custom-checkbox">
                                                            <input type="checkbox" name="fee_structure_ids[]" value="{{ $id }}" class="form-check-input" id="fee_{{ $id }}">
                                                            <label class="form-check-label" for="fee_{{ $id }}">{{ $name }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="col-12 text-center py-3 text-muted">
                                                    {{ __('invoice.no_fees_found') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Student Selection & Filtering --}}
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="text-primary mb-0">2. Select Students <span class="badge badge-primary ms-2" id="studentCountBadge">0</span></h5>
                                        
                                        <div class="d-flex gap-3">
                                            {{-- Student Filter Search --}}
                                            <input type="text" id="studentSearch" class="form-control form-control-sm" style="width: 200px;" placeholder="Search Name or ID...">
                                            
                                            <div class="form-check custom-checkbox pt-1">
                                                <input type="checkbox" class="form-check-input" id="checkAllStudents" checked disabled>
                                                <label class="form-check-label" for="checkAllStudents">{{ __('invoice.select_all') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive border rounded" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover mb-0" id="studentTable">
                                            <thead class="bg-light sticky-top" style="top: 0; z-index: 1;">
                                                <tr>
                                                    <th style="width: 50px;">#</th>
                                                    <th>{{ __('invoice.student_name') }}</th>
                                                    <th>{{ __('invoice.roll_no') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="studentListBody">
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">{{ __('invoice.select_class_first') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted d-block mt-2">{{ __('invoice.student_help') }}</small>
                                </div>
                            </div>

                            <div class="text-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                    <i class="fa fa-file-text-o me-2"></i> {{ __('invoice.generate_btn') }}
                                </button>
                            </div>
                        </form>
                    </div>
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
        
        // Refresh SelectPicker
        if(jQuery().selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

        // 1. AJAX Load Students
        $('#classSectionSelect').change(function(){
            let classId = $(this).val();
            let $tbody = $('#studentListBody');
            let $countBadge = $('#studentCountBadge');
            
            $tbody.html('<tr><td colspan="3" class="text-center"><i class="fa fa-spinner fa-spin"></i> {{ __('invoice.loading') }}...</td></tr>');
            $('#checkAllStudents').prop('disabled', true);
            $countBadge.text('0');

            if(classId) {
                $.ajax({
                    url: "{{ route('invoices.get_students') }}",
                    data: { class_section_id: classId },
                    success: function(data) {
                        $tbody.empty();
                        
                        if(data.length > 0) {
                            $countBadge.text(data.length); // Update Class Size
                            
                            $.each(data, function(key, val){
                                $tbody.append(`
                                    <tr class="student-row">
                                        <td>
                                            <div class="form-check custom-checkbox">
                                                <input type="checkbox" name="student_ids[]" value="${val.id}" class="form-check-input student-checkbox" checked>
                                                <label class="form-check-label"></label>
                                            </div>
                                        </td>
                                        <td class="student-name">${val.name}</td>
                                        <td class="student-roll">${val.roll_no}</td>
                                    </tr>
                                `);
                            });
                            // Reset UI
                            $('#checkAllStudents').prop('checked', true).prop('disabled', false);
                            $('#studentSearch').val(''); // Clear search
                        } else {
                            $tbody.html('<tr><td colspan="3" class="text-center text-danger">{{ __('invoice.no_students_found') }}</td></tr>');
                        }
                    },
                    error: function() {
                        $tbody.html('<tr><td colspan="3" class="text-center text-danger">{{ __('invoice.error_loading') }}</td></tr>');
                        $countBadge.text('0');
                    }
                });
            } else {
                 $tbody.html('<tr><td colspan="3" class="text-center text-muted">{{ __('invoice.select_class_first') }}</td></tr>');
            }
        });

        // 2. Client-Side Search Filter
        $('#studentSearch').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $("#studentListBody tr.student-row").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // 3. Select All Logic
        $('#checkAllStudents').on('change', function() {
            // Only toggle visible rows (allows filtering then selecting all visible)
            let isChecked = $(this).prop('checked');
            $('.student-checkbox:visible').prop('checked', isChecked);
        });

        // 4. Submit Form
        $('#invoiceForm').submit(function(e){
            e.preventDefault();
            
            // Validation: At least one student selected
            if ($('.student-checkbox:checked').length === 0) {
                Swal.fire('Warning', '{{ __('invoice.select_at_least_one_student') }}', 'warning');
                return;
            }
            
            // Validation: At least one fee selected
            if ($('input[name="fee_structure_ids[]"]:checked').length === 0) {
                 Swal.fire('Warning', '{{ __('invoice.select_at_least_one_fee') }}', 'warning');
                 return;
            }

            let btn = $(this).find('button[type="submit"]');
            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __('invoice.processing') }}');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: "{{ __('invoice.success') }}",
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    btn.prop('disabled', false).html(originalText);
                    let msg = xhr.responseJSON.message || "{{ __('invoice.error_occurred') }}";
                    Swal.fire({ icon: 'error', title: "{{ __('invoice.error') }}", text: msg });
                }
            });
        });
    });
</script>
@endsection