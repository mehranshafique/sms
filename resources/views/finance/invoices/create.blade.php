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
                    <div class="card-body">
                        <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                            @csrf
                            <div class="row">
                                {{-- Select Class --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('invoice.target_class') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" class="form-control default-select" required>
                                        <option value="">{{ __('invoice.select_class') }}</option>
                                        @foreach($classes as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('invoice.fee_help') }}</small>
                                </div>

                                {{-- Dates --}}
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('invoice.issue_date') }}</label>
                                    <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('invoice.due_date') }}</label>
                                    <input type="date" name="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                                </div>

                                {{-- Fee Structures --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('invoice.select_fees') }} <span class="text-danger">*</span></label>
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
                                            <div class="col-12 text-danger">No fee structures found. Please create fee structures first.</div>
                                        @endif
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
        $('#invoiceForm').submit(function(e){
            e.preventDefault();
            let btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text("{{ __('invoice.processing') }}");

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
                    btn.prop('disabled', false).text("{{ __('invoice.generate_btn') }}");
                    let msg = xhr.responseJSON.message || "{{ __('invoice.error_occurred') }}";
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });
</script>
@endsection