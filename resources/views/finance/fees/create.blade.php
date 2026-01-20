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
                                            {{-- Populated via JS --}}
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
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- HELPER: Refresh UI Library (Bootstrap-Select) ---
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if ($.fn.selectpicker) {
                     $(element).selectpicker('refresh');
                }
            }
        }

        // --- DOM Elements ---
        const gradeSelect = document.getElementById('gradeSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        const paymentMode = document.getElementById('paymentMode');
        const installmentOrderDiv = document.getElementById('installmentOrderDiv');
        const feeForm = document.getElementById('feeForm');

        // --- 1. Grade Dropdown Logic (Native JS) ---
        if (gradeSelect) {
            gradeSelect.addEventListener('change', function() {
                const gradeId = this.value;
                
                // Reset Section UI
                sectionSelect.innerHTML = '<option value="">{{ __("finance.all_sections") }}</option>';
                refreshSelect(sectionSelect);

                if (gradeId) {
                    // Using established route 'students.get_sections'
                    fetch(`{{ route('students.get_sections') }}?grade_id=${gradeId}`)
                        .then(response => response.json())
                        .then(data => {
                            // Iterate object {id: name}
                            Object.entries(data).forEach(([key, value]) => {
                                let option = new Option(value, key);
                                sectionSelect.add(option);
                            });
                            refreshSelect(sectionSelect);
                        })
                        .catch(err => {
                            console.error('Error loading sections:', err);
                        });
                }
            });
        }

        // --- 2. Payment Mode Toggle ---
        if (paymentMode) {
            paymentMode.addEventListener('change', function() {
                if(this.value === 'installment') {
                    installmentOrderDiv.classList.remove('d-none');
                } else {
                    installmentOrderDiv.classList.add('d-none');
                }
            });
        }

        // --- 3. Form Submission (Native JS Fetch) ---
        if (feeForm) {
            feeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                const formData = new FormData(this);

                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

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
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Success', 
                            text: res.body.message 
                        }).then(() => {
                            window.location.href = res.body.redirect;
                        });
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        let msg = res.body.message || 'Error occurred';
                        Swal.fire({ icon: 'error', title: 'Error', html: msg });
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred.' });
                });
            });
        }
    });
</script>
@endsection