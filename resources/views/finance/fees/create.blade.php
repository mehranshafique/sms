@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('finance.add_fee') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('fees.index') }}">{{ __('finance.fee_structure_title') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('finance.add_fee') }}</a></li>
                </ol>
            </div>
        </div>

        <form action="{{ route('fees.store') }}" method="POST" id="feeForm">
            @csrf
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('finance.add_fee') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="basic-form">
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('finance.fee_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required placeholder="e.g. Tuition Grade 1">
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('finance.fee_type') }} <span class="text-danger">*</span></label>
                                        <select name="fee_type_id" class="form-control default-select" data-live-search="true" required>
                                            <option value="">{{ __('finance.select_type') }}</option>
                                            @foreach($feeTypes as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.amount') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="amount" class="form-control" required min="0" step="0.01">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.frequency') }}</label>
                                        <select name="frequency" class="form-control default-select">
                                            <option value="termly">{{ __('finance.termly') }}</option>
                                            <option value="monthly">{{ __('finance.monthly') }}</option>
                                            <option value="yearly">{{ __('finance.yearly') }}</option>
                                            <option value="one_time">{{ __('finance.one_time') }}</option>
                                        </select>
                                    </div>
                                    
                                    {{-- Grade Level Selector --}}
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.grade_level') }}</label>
                                        <select name="grade_level_id" id="gradeSelect" class="form-control default-select" data-live-search="true">
                                            <option value="">All Grades</option>
                                            @foreach($gradeLevels as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- NEW: Class Section (Optional) --}}
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.class_section') }} <small>({{ __('finance.optional') }})</small></label>
                                        <select name="class_section_id" id="sectionSelect" class="form-control default-select" data-live-search="true">
                                            <option value="">{{ __('finance.all_sections') }}</option>
                                            {{-- Populated via AJAX --}}
                                        </select>
                                    </div>

                                    {{-- Payment Mode --}}
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.payment_mode') }}</label>
                                        <select name="payment_mode" id="paymentMode" class="form-control default-select">
                                            <option value="global">{{ __('finance.global') }}</option>
                                            <option value="installment">{{ __('finance.installment') }}</option>
                                        </select>
                                    </div>

                                    {{-- Installment Order (Visible only if installment) --}}
                                    <div class="mb-3 col-md-4 d-none" id="installmentOrderDiv">
                                        <label class="form-label">{{ __('finance.installment_order') }}</label>
                                        <input type="number" name="installment_order" class="form-control" placeholder="1, 2, 3..." min="1">
                                        <small class="text-muted">{{ __('finance.sequence_order_hint') }}</small>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">{{ __('finance.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        
        if(jQuery().selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

        // Toggle Installment Order
        $('#paymentMode').change(function() {
            if($(this).val() === 'installment') {
                $('#installmentOrderDiv').removeClass('d-none');
            } else {
                $('#installmentOrderDiv').addClass('d-none');
            }
        });

        // AJAX: Fetch Sections when Grade is selected
        $('#gradeSelect').change(function(){
            let gradeId = $(this).val();
            let $sectionSelect = $('#sectionSelect');
            
            // Clear current options
            $sectionSelect.html('<option value="">{{ __("finance.all_sections") }}</option>');
            $sectionSelect.selectpicker('refresh');

            if(gradeId) {
                $.ajax({
                    url: "{{ route('fees.get_sections') }}", // Ensure this route is defined in web.php
                    type: "GET",
                    data: { grade_id: gradeId },
                    success: function(data) {
                        $.each(data, function(id, name){
                            $sectionSelect.append('<option value="'+id+'">'+name+'</option>');
                        });
                        $sectionSelect.selectpicker('refresh');
                    },
                    error: function() {
                        console.error("Failed to fetch sections");
                    }
                });
            }
        });

        $('#feeForm').submit(function(e){
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    let msg = xhr.responseJSON.message || 'Error occurred';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });
</script>
@endsection