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
                            {{-- Class Section Filter --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('reminders.target_class') ?? 'Target Class & Section (Optional)' }}</label>
                                <select name="class_section_id" class="form-control default-select" data-live-search="true">
                                    <option value="">{{ __('reminders.all_classes') ?? 'All Classes & Sections' }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->gradeLevel->name ?? '' }} - {{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

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
                                <select name="channel" id="fee_channel" class="form-control default-select" required>
                                    <option value="sms">{{ __('reminders.standard_sms') }}</option>
                                    <option value="whatsapp">{{ __('reminders.whatsapp') }}</option>
                                    <option value="email">{{ __('reminders.email') ?? 'Email' }}</option>
                                </select>
                            </div>
                            @if(!empty($planCtx['has_ai']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('ai.preview_message') }}</label>
                                <textarea id="fee_message_preview" class="form-control" rows="3" placeholder="{{ __('ai.btn_draft_reminder') }}…"></textarea>
                                <div class="mt-2">
                                    @include('ai.partials.embed-button', [
                                        'tool' => 'draft_fee_reminder',
                                        'params' => [],
                                        'fields' => [
                                            'class_section_id' => '#feeReminderForm select[name=class_section_id]',
                                            'fee_structure_id' => '#feeReminderForm select[name=fee_structure_id]',
                                            'channel' => '#fee_channel',
                                        ],
                                        'label' => __('ai.btn_draft_reminder'),
                                        'target' => '#fee_message_preview',
                                    ])
                                </div>
                            </div>
                            @endif
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
                            
                            {{-- Class Section Filter --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('reminders.target_class') ?? 'Target Class & Section (Optional)' }}</label>
                                <select name="class_section_id" class="form-control default-select" data-live-search="true">
                                    <option value="">{{ __('reminders.all_classes') ?? 'All Classes & Sections' }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->gradeLevel->name ?? '' }} - {{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4 mt-2">
                                <label class="form-label fw-bold">{{ __('reminders.delivery_channel') }}</label>
                                <select name="channel" id="exam_channel" class="form-control default-select" required>
                                    <option value="sms">{{ __('reminders.standard_sms') }}</option>
                                    <option value="whatsapp">{{ __('reminders.whatsapp') }}</option>
                                    <option value="email">{{ __('reminders.email') ?? 'Email' }}</option>
                                </select>
                            </div>
                            @if(!empty($planCtx['has_ai']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('ai.preview_message') }}</label>
                                <textarea id="exam_message_preview" class="form-control" rows="3"></textarea>
                                <div class="mt-2">
                                    @include('ai.partials.embed-button', [
                                        'tool' => 'draft_exam_reminder',
                                        'params' => [],
                                        'fields' => [
                                            'class_section_id' => '#examReminderForm select[name=class_section_id]',
                                            'channel' => '#exam_channel',
                                        ],
                                        'label' => __('ai.btn_draft_reminder'),
                                        'target' => '#exam_message_preview',
                                    ])
                                </div>
                            </div>
                            @endif
                            <div class="alert alert-info light border-info mb-4">
                                <i class="fa fa-info-circle me-2 fs-16"></i> {{ __('reminders.exam_info') }}
                            </div>
                            <button type="submit" class="btn btn-success submit-btn w-100 shadow"><i class="fa fa-calendar-check-o me-2"></i> {{ __('reminders.trigger_exam_reminders') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-xl-12">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h4 class="card-title"><i class="fa fa-chart-line text-info me-2"></i> {{ __('reminders.attendance_reports_title') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">{{ __('reminders.attendance_reports_desc') }}</p>
                        <form id="attendanceReportForm" action="{{ route('reminders.attendance.send') }}" method="POST" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('reminders.attendance_period') }}</label>
                                <select name="period_type" class="form-control default-select" required>
                                    <option value="week">{{ __('attendance.this_week') }}</option>
                                    <option value="month">{{ __('attendance.this_month') }}</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-info submit-btn w-100 shadow text-white">
                                    <i class="fa fa-paper-plane me-2"></i> {{ __('reminders.send_attendance_reports') }}
                                </button>
                            </div>
                        </form>
                        <div class="alert alert-light border mt-3 mb-0">
                            <i class="fa fa-clock-o me-1"></i> {{ __('reminders.attendance_auto_schedule') }}
                        </div>
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
                    html: (function () {
                        var previewId = formId === '#feeReminderForm' ? '#fee_message_preview' : (formId === '#examReminderForm' ? '#exam_message_preview' : null);
                        var preview = previewId ? $(previewId).val() : '';
                        if (preview && preview.trim()) {
                            return lang.broadcastWarning + '<hr><p class="text-start small fw-bold mb-1">{{ __('ai.preview_message') }}</p><div class="text-start small border rounded p-2 bg-light" style="white-space:pre-wrap">' + $('<div>').text(preview).html() + '</div>';
                        }
                        return lang.broadcastWarning;
                    })(),
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
        handleForm('#attendanceReportForm');
    });
</script>
@endsection