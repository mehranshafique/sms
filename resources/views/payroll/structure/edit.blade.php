@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('payroll.setup_salary') }}</h4>
                    <p class="mb-0">{{ $staff->full_name }} ({{ $staff->designation }})</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('salary-structures.index') }}" class="btn btn-secondary">{{ __('payroll.back') ?? 'Back' }}</a>
            </div>
        </div>

        <form action="{{ route('salary-structures.update', $staff->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                {{-- Base Info --}}
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('payroll.base_salary') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('payroll.payment_basis') }}</label>
                                    <select name="payment_basis" class="form-control default-select">
                                        <option value="monthly" {{ $structure->payment_basis == 'monthly' ? 'selected' : '' }}>{{ __('payroll.monthly') }}</option>
                                        <option value="hourly" {{ $structure->payment_basis == 'hourly' ? 'selected' : '' }}>{{ __('payroll.hourly') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('payroll.base_salary') }} / {{ __('payroll.hourly_rate') }}</label>
                                    <input type="number" name="base_salary" class="form-control" value="{{ $structure->base_salary }}" required step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Allowances --}}
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h4 class="card-title text-success">{{ __('payroll.allowances') }} (+)</h4>
                            <button type="button" class="btn btn-xs btn-primary add-row" data-target="allowance_container">{{ __('payroll.add_row') }}</button>
                        </div>
                        <div class="card-body" id="allowance_container">
                            @php $allowances = $structure->allowances ?? []; @endphp
                            @if(empty($allowances))
                                {{-- Empty Row --}}
                                <div class="row mb-2 entry-row">
                                    <div class="col-6">
                                        <input type="text" name="allowance_keys[]" class="form-control" placeholder="{{ __('payroll.allowance_label') }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="allowance_values[]" class="form-control" placeholder="0.00" step="0.01">
                                    </div>
                                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></div>
                                </div>
                            @else
                                @foreach($allowances as $key => $val)
                                <div class="row mb-2 entry-row">
                                    <div class="col-6">
                                        <input type="text" name="allowance_keys[]" class="form-control" value="{{ $key }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="allowance_values[]" class="form-control" value="{{ $val }}" step="0.01">
                                    </div>
                                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Deductions --}}
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h4 class="card-title text-danger">{{ __('payroll.deductions') }} (-)</h4>
                            <button type="button" class="btn btn-xs btn-primary add-row" data-target="deduction_container">{{ __('payroll.add_row') }}</button>
                        </div>
                        <div class="card-body" id="deduction_container">
                            @php $deductions = $structure->deductions ?? []; @endphp
                            @if(empty($deductions))
                                <div class="row mb-2 entry-row">
                                    <div class="col-6">
                                        <input type="text" name="deduction_keys[]" class="form-control" placeholder="{{ __('payroll.deduction_label') }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="deduction_values[]" class="form-control" placeholder="0.00" step="0.01">
                                    </div>
                                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></div>
                                </div>
                            @else
                                @foreach($deductions as $key => $val)
                                <div class="row mb-2 entry-row">
                                    <div class="col-6">
                                        <input type="text" name="deduction_keys[]" class="form-control" value="{{ $key }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="deduction_values[]" class="form-control" value="{{ $val }}" step="0.01">
                                    </div>
                                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success btn-lg">{{ __('payroll.save_changes') ?? 'Save' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Add Row Logic
        $('.add-row').click(function() {
            let targetId = $(this).data('target');
            let namePrefix = targetId === 'allowance_container' ? 'allowance' : 'deduction';
            
            let html = `
                <div class="row mb-2 entry-row">
                    <div class="col-6">
                        <input type="text" name="${namePrefix}_keys[]" class="form-control" placeholder="Label">
                    </div>
                    <div class="col-4">
                        <input type="number" name="${namePrefix}_values[]" class="form-control" placeholder="0.00" step="0.01">
                    </div>
                    <div class="col-2"><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></div>
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