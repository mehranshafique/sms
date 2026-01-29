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
                                <input type="text" class="form-control" id="studentName" value="{{ $invoice->student->full_name }}" readonly disabled>
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
                                <input type="number" name="amount" id="paymentAmount" class="form-control" max="{{ $invoice->total_amount - $invoice->paid_amount }}" required step="0.01">
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
            
            let btn = $(this).find('button[type="submit"]');
            let studentName = $('#studentName').val();
            let amount = $('#paymentAmount').val();

            // SweetAlert Popup for Confirmation & Password
            Swal.fire({
                title: '{{ __("payment.confirm_title") }}',
                html: `
                    <div class="text-start mb-3">
                        <p>{{ __("payment.confirm_message", ["name" => "REPLACE_NAME"]) }}`.replace('REPLACE_NAME', studentName) + `</p>
                        <p class="mb-0">{{ __("payment.amount_to_pay") }}: <span class="text-success fw-bold">${amount}</span></p>
                    </div>
                `,
                input: 'password',
                inputLabel: '{{ __("payment.password_label") }}',
                inputPlaceholder: '{{ __("payment.password_placeholder") }}',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocorrect: 'off',
                    required: 'true'
                },
                showCancelButton: true,
                confirmButtonText: '{{ __("payment.validate_pay_btn") }}',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                showLoaderOnConfirm: true,
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('{{ __("payment.password_required") }}');
                    }
                    return password;
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    let password = result.value;
                    
                    // Create FormData and append password
                    let formData = new FormData(this);
                    formData.append('password', password);

                    btn.prop('disabled', true).text('Processing...');

                    $.ajax({
                        url: $(this).attr('action'),
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response){
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("payment.success") }}',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        },
                        error: function(xhr){
                            btn.prop('disabled', false).text('{{ __("payment.confirm_payment") }}');
                            let msg = xhr.responseJSON ? xhr.responseJSON.message : '{{ __("payment.error_occurred") }}';
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("payment.error") }}',
                                text: msg
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endsection