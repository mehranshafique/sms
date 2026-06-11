@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-3">
            <div class="col-sm-8">
                <h4>{{ __('payment_proof.page_title') }}</h4>
                <p class="text-muted mb-0">{{ __('payment_proof.subtitle') }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('payment_proof.pending_list') }}</h5>
                <select id="statusFilter" class="form-control form-control-sm w-auto">
                    <option value="pending">{{ __('payment_proof.status_pending') }}</option>
                    <option value="approved">{{ __('payment_proof.status_approved') }}</option>
                    <option value="rejected">{{ __('payment_proof.status_rejected') }}</option>
                    <option value="">{{ __('payment_proof.status_all') }}</option>
                </select>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="proofsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('online_pay.invoice_number') }}</th>
                                <th>{{ __('online_pay.student') }}</th>
                                <th>{{ __('online_pay.payer_name') }}</th>
                                <th>{{ __('online_pay.amount') }}</th>
                                <th>{{ __('online_pay.method') }}</th>
                                <th>{{ __('payment_proof.paid_at') }}</th>
                                <th>{{ __('online_pay.mobile_reference') }}</th>
                                <th>{{ __('payment_proof.receipt') }}</th>
                                <th>{{ __('payment_proof.status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    const table = $('#proofsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('payment-proofs.index')),
            data: function(d) { d.status = $('#statusFilter').val(); }
        },
        columns: [
            { data: 'invoice_no', name: 'invoice_id' },
            { data: 'student', name: 'student' },
            { data: 'payer_name', name: 'payer_name' },
            { data: 'amount', name: 'amount' },
            { data: 'method_label', name: 'method' },
            { data: 'paid_at_fmt', name: 'paid_at' },
            { data: 'transaction_reference', name: 'transaction_reference' },
            { data: 'receipt', name: 'receipt', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[5, 'desc']]
    });

    $('#statusFilter').on('change', () => table.ajax.reload());

    function postProofAction(url, data, onSuccess) {
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            skipDigitexLoader: true,
            headers: { 'Accept': 'application/json' },
        }).done(function(res) {
            if (typeof window.digitexNotifySuccess === 'function') {
                window.digitexNotifySuccess(res.message);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: @json(__('attendance.success')), text: res.message, timer: 2000, showConfirmButton: false });
            }
            onSuccess();
        }).fail(function(xhr) {
            $('#digitex-ajax-loader').removeClass('is-visible');
            const msg = xhr.responseJSON?.message || @json(__('payment_proof.action_failed'));
            if (typeof window.digitexNotifyError === 'function') {
                window.digitexNotifyError(msg);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: @json(__('attendance.error_occurred')), text: msg });
            }
        }).always(function() {
            $('#digitex-ajax-loader').removeClass('is-visible');
        });
    }

    $(document).on('click', '.approve-proof', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: @json(__('payment_proof.confirm_approve')),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: @json(__('payment_proof.approve')),
            focusConfirm: true,
            didOpen: function() {
                $('.bootstrap-select.open, .bootstrap-select.show').removeClass('open show');
            },
        }).then(function(r) {
            if (!r.isConfirmed) return;
            const approveUrl = @json(route('payment-proofs.approve', ['proof' => '__ID__'])).replace('__ID__', id);
            postProofAction(approveUrl, { _token: @json(csrf_token()) }, function() {
                table.ajax.reload(null, false);
            });
        });
    });

    $(document).on('click', '.reject-proof', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: @json(__('payment_proof.confirm_reject')),
            input: 'text',
            inputPlaceholder: @json(__('payment_proof.rejection_reason')),
            inputValue: '',
            showCancelButton: true,
            focusConfirm: false,
            didOpen: function() {
                $('.bootstrap-select.open, .bootstrap-select.show').removeClass('open show');
            },
        }).then(function(r) {
            if (!r.isConfirmed) return;
            const rejectUrl = @json(route('payment-proofs.reject', ['proof' => '__ID__'])).replace('__ID__', id);
            postProofAction(rejectUrl, { _token: @json(csrf_token()), reason: r.value || '' }, function() {
                table.ajax.reload(null, false);
            });
        });
    });
});
</script>
@endsection
