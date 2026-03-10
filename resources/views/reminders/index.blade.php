@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4><i class="fa fa-paper-plane text-primary me-2"></i> {{ __('reminders.page_title') }}</h4>
                    <p class="mb-0 text-muted">{{ __('reminders.page_subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Fee Reminders Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h4 class="card-title"><i class="fa fa-money text-warning me-2"></i> {{ __('reminders.fee_reminders_title') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">{{ __('reminders.fee_reminders_desc') }}</p>
                        <form id="feeReminderForm" action="{{ route('reminders.fees.send') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('reminders.target_tranche') }}</label>
                                <select name="fee_structure_id" class="form-control default-select">
                                    <option value="">{{ __('reminders.all_unpaid_tranches') }}</option>
                                    @foreach($feeStructures as $fs)
                                        <option value="{{ $fs->id }}">{{ $fs->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted mt-2 d-block bg-light p-2 rounded border border-light">
                                    <i class="fa fa-info-circle text-info me-1"></i> {!! __('reminders.tranche_info') !!}
                                </small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('reminders.delivery_channel') }}</label>
                                <select name="channel" class="form-control default-select" required>
                                    <option value="sms">{{ __('reminders.standard_sms') }}</option>
                                    <option value="whatsapp">{{ __('reminders.whatsapp') }}</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary submit-btn w-100 shadow"><i class="fa fa-paper-plane me-2"></i> {{ __('reminders.send_fee_reminders') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Exam Reminders Card -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h4 class="card-title"><i class="fa fa-calendar-check-o text-success me-2"></i> {{ __('reminders.exam_reminders_title') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">{!! __('reminders.exam_reminders_desc') !!}</p>
                        <form id="examReminderForm" action="{{ route('reminders.exams.send') }}" method="POST">
                            @csrf
                            <div class="mb-4 mt-2">
                                <label class="form-label fw-bold">{{ __('reminders.delivery_channel') }}</label>
                                <select name="channel" class="form-control default-select" required>
                                    <option value="sms">{{ __('reminders.standard_sms') }}</option>
                                    <option value="whatsapp">{{ __('reminders.whatsapp') }}</option>
                                </select>
                            </div>
                            <div class="alert alert-info light border-info mb-4">
                                <i class="fa fa-info-circle me-2 fs-16"></i> {{ __('reminders.exam_info') }}
                            </div>
                            <button type="submit" class="btn btn-success submit-btn w-100 shadow"><i class="fa fa-calendar-check-o me-2"></i> {{ __('reminders.trigger_exam_reminders') }}</button>
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
    $(document).ready(function() {
        if($.fn.selectpicker) { $('.default-select').selectpicker('refresh'); }

        const lang = {
            initiateBroadcast: @json(__('reminders.initiate_broadcast')),
            broadcastWarning: @json(__('reminders.broadcast_warning')),
            yesDispatch: @json(__('reminders.yes_dispatch')),
            dispatching: @json(__('reminders.dispatching')),
            finished: @json(__('reminders.finished')),
            error: @json(__('reminders.error')),
            gatewayError: @json(__('reminders.gateway_error'))
        };

        function handleForm(formId) {
            $(formId).submit(function(e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('.submit-btn');
                let originalText = btn.html();

                Swal.fire({
                    title: lang.initiateBroadcast,
                    text: lang.broadcastWarning,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '<i class="fa fa-paper-plane me-1"></i> ' + lang.yesDispatch
                }).then((result) => {
                    if (result.isConfirmed) {
                        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> ' + lang.dispatching);
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function(response) {
                                btn.prop('disabled', false).html(originalText);
                                let icon = response.status === 'success' ? 'success' : 'info';
                                Swal.fire({ icon: icon, title: lang.finished, text: response.message });
                            },
                            error: function(xhr) {
                                btn.prop('disabled', false).html(originalText);
                                let msg = xhr.responseJSON ? (xhr.responseJSON.message || lang.gatewayError) : lang.gatewayError;
                                Swal.fire({ icon: 'error', title: lang.error, text: msg });
                            }
                        });
                    }
                });
            });
        }

        handleForm('#feeReminderForm');
        handleForm('#examReminderForm');
    });
</script>
@endsection