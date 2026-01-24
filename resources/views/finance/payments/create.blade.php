@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('payment.record_payment') }}</h4>
                    <p class="mb-0">{{ __('payment.invoice_no') }} #{{ $invoice->invoice_number }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('payment.payment_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('payments.store') }}" method="POST" id="paymentForm">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                            
                            <div class="mb-3">
                                <label class="form-label">{{ __('payment.student_name') }}</label>
                                <input type="text" class="form-control" value="{{ $invoice->student->full_name }}" readonly disabled>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">{{ __('payment.total_amount') }}</label>
                                    <input type="text" class="form-control" value="{{ number_format($invoice->total_amount, 2) }}" readonly disabled>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-danger">{{ __('payment.remaining_balance') }}</label>
                                    <input type="text" class="form-control text-danger fw-bold" value="{{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}" readonly disabled>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('payment.payment_amount') }} <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" max="{{ $invoice->total_amount - $invoice->paid_amount }}" required step="0.01">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('payment.payment_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('payment.method') }} <span class="text-danger">*</span></label>
                                <select name="method" class="form-control default-select">
                                    <option value="cash">{{ __('payment.cash') }}</option>
                                    <option value="bank_transfer">{{ __('payment.bank_transfer') }}</option>
                                    <option value="card">{{ __('payment.card') }}</option>
                                    <option value="online">{{ __('payment.online') }}</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('payment.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-danger font-w600"><i class="fa fa-lock me-1"></i> Admin Password Confirmation <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control border-danger" required placeholder="Enter your login password to confirm">
                            </div>

                            <button type="submit" class="btn btn-success w-100">{{ __('payment.confirm_payment') }}</button>
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
        $('#paymentForm').submit(function(e){
            e.preventDefault();
            
            // Disable button to prevent double submit
            let btn = $(this).find('button[type="submit"]');
            let originalText = btn.text();
            btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire('{{ __("payment.success") }}', response.message, 'success').then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    btn.prop('disabled', false).text(originalText);
                    let msg = xhr.responseJSON ? xhr.responseJSON.message : '{{ __("payment.error_occurred") }}';
                    Swal.fire('{{ __("payment.error") }}', msg, 'error');
                }
            });
        });
    });
</script>
@endsection