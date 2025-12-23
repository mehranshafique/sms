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

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('invoice.invoice_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                            @csrf
                            <div class="row">
                                {{-- 1. Grade Level (First Step) --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('invoice.target_grade') }} <span class="text-danger">*</span></label>
                                    <select name="grade_id" id="gradeSelect" class="form-control default-select" data-live-search="true" required>
                                        <option value="">{{ __('invoice.select_grade') }}</option>
                                        @foreach($grades as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 2. Class Section (Second Step - Dependent) --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('invoice.target_section') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" id="sectionSelect" class="form-control default-select" required disabled>
                                        <option value="">{{ __('invoice.select_grade_first') }}</option>
                                    </select>
                                    <small class="text-muted">{{ __('invoice.fee_help') }}</small>
                                </div>

                                {{-- Dates --}}
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('invoice.issue_date') }}</label>
                                    <input type="text" name="issue_date" class="form-control datepicker" value="{{ date('Y-m-d') }}" placeholder="YYYY-MM-DD" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('invoice.due_date') }}</label>
                                    <input type="text" name="due_date" class="form-control datepicker" value="{{ date('Y-m-d', strtotime('+30 days')) }}" placeholder="YYYY-MM-DD" required>
                                </div>

                                {{-- Fee Structures --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('invoice.select_fees') }} <span class="text-danger">*</span></label>
                                    <div class="border rounded p-3">
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
                                                <div class="col-12 text-danger">{{ __('invoice.no_fees_found') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('invoice.generate_btn') }}</button>
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
        
        // Refresh SelectPicker on Load
        if(jQuery().selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

        // AJAX: Load Sections based on Grade
        $('#gradeSelect').change(function(){
            let gradeId = $(this).val();
            let sectionSelect = $('#sectionSelect');
            
            // UI Feedback: Loading State
            sectionSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
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
                        
                        // FIX: Wrap refresh in setTimeout to prevent Grade dropdown from resetting
                        setTimeout(function() {
                            if($.fn.selectpicker) {
                                sectionSelect.selectpicker('refresh');
                            }
                        }, 100);
                    },
                    error: function(){
                        sectionSelect.empty().append('<option value="">Error loading data</option>');
                        if($.fn.selectpicker) sectionSelect.selectpicker('refresh');
                    }
                });
            } else {
                sectionSelect.empty().append('<option value="">{{ __("invoice.select_grade_first") }}</option>');
                if($.fn.selectpicker) sectionSelect.selectpicker('refresh');
            }
        });

        // Form Submit
        $('#invoiceForm').submit(function(e){
            e.preventDefault();
            let btn = $(this).find('button[type="submit"]');
            let originalText = btn.text();
            btn.prop('disabled', true).text("{{ __('invoice.processing') }}");

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    btn.prop('disabled', false).text(originalText);
                    let msg = xhr.responseJSON.message || "{{ __('invoice.error_occurred') }}";
                    if(xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: msg
                    });
                }
            });
        });
    });
</script>
@endsection