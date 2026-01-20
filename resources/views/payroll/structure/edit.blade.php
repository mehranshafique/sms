@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        {{-- Header --}}
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('payroll.salary_structure') }}</h4>
                    <p class="mb-0 text-muted">{{ __('payroll.configure_rules') }} <strong class="text-primary">{{ $staff->full_name }}</strong></p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('salary-structures.index') }}" class="btn btn-light btn-sm shadow">
                    <i class="fa fa-arrow-left me-1"></i> {{ __('payroll.back_to_list') }}
                </a>
            </div>
        </div>

        <form action="{{ route('salary-structures.update', $staff->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                
                {{-- 1. Base Configuration --}}
                <div class="col-xl-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title text-white mb-0"><i class="fa fa-money me-2"></i> {{ __('payroll.base_configuration') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label font-w600">{{ __('payroll.payment_basis') }} <span class="text-danger">*</span></label>
                                    <select name="payment_basis" id="paymentBasis" class="form-control default-select wide">
                                        <option value="monthly" {{ $structure->payment_basis == 'monthly' ? 'selected' : '' }}>
                                            {{ __('payroll.monthly_desc') }}
                                        </option>
                                        <option value="hourly" {{ $structure->payment_basis == 'hourly' ? 'selected' : '' }}>
                                            {{ __('payroll.hourly_desc') }}
                                        </option>
                                    </select>
                                    <small class="text-muted d-block mt-1" id="basisHelp">
                                        {{-- Populated via JS --}}
                                    </small>
                                </div>
                                <div class="col-md-7 mb-3">
                                    <label class="form-label font-w600" id="baseLabel">{{ __('payroll.base_salary') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ config('app.currency_symbol', '$') }}</span>
                                        <input type="number" name="base_salary" class="form-control" value="{{ $structure->base_salary }}" required step="0.01" placeholder="{{ __('payroll.amount_placeholder') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Allowances --}}
                <div class="col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header border-bottom">
                            <h5 class="card-title text-success"><i class="fa fa-plus-circle me-2"></i> {{ __('payroll.allowances') }}</h5>
                            <button type="button" class="btn btn-success btn-xs shadow sharp add-row" data-target="allowance_container"><i class="fa fa-plus"></i></button>
                        </div>
                        <div class="card-body bg-light" id="allowance_container" style="min-height: 200px;">
                            <p class="text-muted fs-12 mb-3">{{ __('payroll.allowance_help') }}</p>
                            @php 
                                $allowances = $structure->allowances;
                                // Safety Decode: Ensure it's an array if passed as string
                                if(is_string($allowances)) $allowances = json_decode($allowances, true);
                                if(!is_array($allowances)) $allowances = [];
                            @endphp
                            @forelse($allowances as $key => $val)
                                @php
                                    // Handle array format [{'name'=>'Transport', 'amount'=>50}] vs associative ['Transport'=>50]
                                    $label = is_array($val) ? ($val['name'] ?? '') : $key;
                                    $amount = is_array($val) ? ($val['amount'] ?? 0) : $val;
                                @endphp
                                <div class="row mb-2 entry-row align-items-center">
                                    <div class="col-6">
                                        <input type="text" name="allowance_keys[]" class="form-control form-control-sm" value="{{ $label }}" placeholder="{{ __('payroll.label_placeholder') }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="allowance_values[]" class="form-control form-control-sm" value="{{ $amount }}" step="0.01">
                                    </div>
                                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs sharp remove-row"><i class="fa fa-trash"></i></button></div>
                                </div>
                            @empty
                                {{-- Empty Default --}}
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- 3. Deductions --}}
                <div class="col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header border-bottom">
                            <h5 class="card-title text-danger"><i class="fa fa-minus-circle me-2"></i> {{ __('payroll.deductions') }}</h5>
                            <button type="button" class="btn btn-danger btn-xs shadow sharp add-row" data-target="deduction_container"><i class="fa fa-plus"></i></button>
                        </div>
                        <div class="card-body bg-light" id="deduction_container" style="min-height: 200px;">
                             <p class="text-muted fs-12 mb-3">{{ __('payroll.deduction_help') }} <br><strong>{{ __('payroll.deduction_note') }}</strong></p>
                            @php 
                                $deductions = $structure->deductions;
                                // Safety Decode
                                if(is_string($deductions)) $deductions = json_decode($deductions, true);
                                if(!is_array($deductions)) $deductions = [];
                            @endphp
                            @forelse($deductions as $key => $val)
                                @php
                                    $label = is_array($val) ? ($val['name'] ?? '') : $key;
                                    $amount = is_array($val) ? ($val['amount'] ?? 0) : $val;
                                @endphp
                                <div class="row mb-2 entry-row align-items-center">
                                    <div class="col-6">
                                        <input type="text" name="deduction_keys[]" class="form-control form-control-sm" value="{{ $label }}" placeholder="{{ __('payroll.label_placeholder') }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="deduction_values[]" class="form-control form-control-sm" value="{{ $amount }}" step="0.01">
                                    </div>
                                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs sharp remove-row"><i class="fa fa-trash"></i></button></div>
                                </div>
                            @empty
                                {{-- Empty Default --}}
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary btn-lg shadow"><i class="fa fa-save me-2"></i> {{ __('payroll.save_structure') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Dynamic UI for Payment Basis
        $('#paymentBasis').change(function() {
            let val = $(this).val();
            if(val === 'hourly') {
                $('#baseLabel').text("{{ __('payroll.hourly_rate_label') }}");
                $('#basisHelp').text("{{ __('payroll.help_hourly') }}");
            } else {
                $('#baseLabel').text("{{ __('payroll.base_salary_label') }}");
                $('#basisHelp').text("{{ __('payroll.help_monthly') }}");
            }
        }).trigger('change');

        // Add Row Logic
        $('.add-row').click(function() {
            let targetId = $(this).data('target');
            let namePrefix = targetId === 'allowance_container' ? 'allowance' : 'deduction';
            
            let html = `
                <div class="row mb-2 entry-row align-items-center">
                    <div class="col-6">
                        <input type="text" name="${namePrefix}_keys[]" class="form-control form-control-sm" placeholder="{{ __('payroll.label_placeholder') }}">
                    </div>
                    <div class="col-4">
                        <input type="number" name="${namePrefix}_values[]" class="form-control form-control-sm" placeholder="{{ __('payroll.amount_placeholder') }}" step="0.01">
                    </div>
                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs sharp remove-row"><i class="fa fa-trash"></i></button></div>
                </div>`;
            
            $('#' + targetId).append(html);
        });

        // Remove Row Logic
        $(document).on('click', '.remove-row', function() {
            $(this).closest('.entry-row').remove();
        });
    });
</script>
@endsection