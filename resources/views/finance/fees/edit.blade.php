@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('finance.edit_fee') ?? 'Edit Fee Structure' }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('fees.index') }}">{{ __('finance.fee_structure_title') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('finance.edit_fee') ?? 'Edit' }}</a></li>
                </ol>
            </div>
        </div>

        {{-- Reuse the existing create form logic if possible, or create a specific edit form. 
             Since _form.blade.php wasn't created for fees in previous steps (it was inline in create), 
             I will create a standard edit form here similar to create. --}}
        
        <form action="{{ route('fees.update', $feeStructure->id) }}" method="POST" id="feeForm">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('finance.edit_fee') ?? 'Edit Fee' }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="basic-form">
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('finance.fee_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" value="{{ old('name', $feeStructure->name) }}" class="form-control" required>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('finance.fee_type') }} <span class="text-danger">*</span></label>
                                        <select name="fee_type_id" class="form-control default-select" required>
                                            <option value="">{{ __('finance.select_type') }}</option>
                                            @foreach($feeTypes as $id => $name)
                                                <option value="{{ $id }}" {{ (old('fee_type_id', $feeStructure->fee_type_id) == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.amount') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="amount" value="{{ old('amount', $feeStructure->amount) }}" class="form-control" required min="0" step="0.01">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.frequency') }}</label>
                                        <select name="frequency" class="form-control default-select">
                                            <option value="termly" {{ (old('frequency', $feeStructure->frequency) == 'termly') ? 'selected' : '' }}>{{ __('finance.termly') }}</option>
                                            <option value="monthly" {{ (old('frequency', $feeStructure->frequency) == 'monthly') ? 'selected' : '' }}>{{ __('finance.monthly') }}</option>
                                            <option value="yearly" {{ (old('frequency', $feeStructure->frequency) == 'yearly') ? 'selected' : '' }}>{{ __('finance.yearly') }}</option>
                                            <option value="one_time" {{ (old('frequency', $feeStructure->frequency) == 'one_time') ? 'selected' : '' }}>{{ __('finance.one_time') }}</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">{{ __('finance.grade_level') }}</label>
                                        <select name="grade_level_id" class="form-control default-select">
                                            <option value="">All Grades</option>
                                            @foreach($gradeLevels as $id => $name)
                                                <option value="{{ $id }}" {{ (old('grade_level_id', $feeStructure->grade_level_id) == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">{{ __('finance.save') ?? 'Update' }}</button>
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