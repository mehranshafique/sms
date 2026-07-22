@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('requests.page_title') }}</h4>
                    <p class="mb-0">{{ __('requests.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2 flex-wrap">
                <select id="classFilter" class="form-control default-select bg-white shadow-sm w-auto">
                    <option value="">{{ __('requests.all_classes') }}</option>
                    @foreach($classes ?? [] as $cls)
                        <option value="{{ $cls->id }}">{{ class_section_label($cls) }}</option>
                    @endforeach
                </select>
                <select id="statusFilter" class="form-control default-select bg-white shadow-sm w-auto">
                    {{-- FIXED: "All Tickets" is now explicitly the selected default --}}
                    <option value="all" selected>{{ __('requests.status_all') }}</option>
                    <option value="submitted">{{ __('requests.status_submitted') }}</option>
                    <option value="pending">{{ __('requests.status_pending_only') }}</option>
                    <option value="under_review">{{ __('requests.status_under_review') }}</option>
                    <option value="approved">{{ __('requests.status_approved') }}</option>
                    <option value="rejected">{{ __('requests.status_rejected') }}</option>
                    <option value="additional_info_required">{{ __('requests.status_additional_info_required') }}</option>
                </select>
                <a href="{{ route('requests.create') }}" class="btn btn-primary shadow-sm">
                    <i class="fa fa-plus me-2"></i> {{ __('requests.create_new') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="requestsTable" class="display table table-striped table-hover" style="width:100%; min-width: 845px;">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('requests.ticket_number') }}</th>
                                        <th>{{ __('requests.applicant') }}</th>
                                        <th>{{ __('requests.request_type') }}</th>
                                        <th>{{ __('requests.classe') }}</th>
                                        <th>{{ __('requests.deadline') }}</th>
                                        <th>{{ __('requests.date_submitted') }}</th>
                                        <th>{{ __('requests.status') }}</th>
                                        <th class="text-end">{{ __('requests.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Ticket Modal -->
<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fa fa-ticket-alt me-2"></i> {{ __('requests.process_ticket') }} <span id="modalTicketNo"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="processForm">
                @csrf
                <div class="modal-body">
                    <!-- Read Only Info -->
                    <div class="bg-light p-3 rounded mb-3 border">
                        <p class="mb-1 text-muted small text-uppercase fw-bold">{{ __('requests.student') }}</p>
                        <h5 id="modalStudentName" class="text-dark mb-3"></h5>
                        
                        <p class="mb-1 text-muted small text-uppercase fw-bold">{{ __('requests.parent_reason') }}</p>
                        <p id="modalReason" class="text-dark bg-white p-2 rounded border-start border-3 border-primary font-italic"></p>
                    </div>

                    <div id="modalDossierWrap" class="mb-3"></div>

                    <!-- Processing Action -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('requests.admin_decision') }} <span class="text-danger">*</span></label>
                        <select name="status" id="actionStatus" class="form-control" required>
                            <option value="">{{ __('requests.select_decision') }}</option>
                            <option value="under_review">🔍 {{ __('requests.status_under_review') }}</option>
                            <option value="approved">✅ {{ __('requests.approve_fully') }}</option>
                            <option value="partially_approved">⚠️ {{ __('requests.partially_approve') }}</option>
                            <option value="rejected">❌ {{ __('requests.reject_request') }}</option>
                            <option value="additional_info_required">📋 {{ __('requests.decision_additional_info') }}</option>
                        </select>
                    </div>

                    <div class="mb-3" id="paymentDeadlineDiv" style="display:none;">
                        <label class="form-label fw-bold">{{ __('requests.payment_deadline') }}</label>
                        <input type="text" name="payment_deadline" id="payment_deadline_input" class="form-control datepicker-modal" placeholder="YYYY-MM-DD" autocomplete="off">
                    </div>

                    <!-- Appears only if Partial is selected -->
                    <div class="mb-3" id="partialDaysDiv" style="display: none;">
                        <label class="form-label fw-bold text-warning">{{ __('requests.approved_duration') }}</label>
                        <input type="number" name="approved_days" class="form-control border-warning" placeholder="e.g. 5" min="1">
                        <small class="text-muted">{{ __('requests.approved_duration_help') }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('requests.admin_note') }} <span class="text-danger">*</span></label>
                        <textarea name="admin_note" id="adminNote" class="form-control" rows="3" placeholder="{{ __('requests.admin_note_placeholder') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('requests.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fab fa-whatsapp me-2"></i> {{ __('requests.save_notify') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // FIXED: Localized JS Strings
    const LANG = {
        processing: @json(__('requests.processing')),
        processed: @json(__('requests.processed')),
        error: @json(__('requests.error')),
        errorOccurred: @json(__('requests.error_occurred')),
        areYouSure: @json(__('requests.are_you_sure')),
        cannotRevert: @json(__('requests.cannot_revert')),
        yesDelete: @json(__('requests.yes_delete')),
        deleted: @json(__('requests.deleted')),
        success: @json(__('requests.success')),
        noReason: @json(__('requests.no_reason_provided') ?? 'No reason provided.')
    };

    $(document).ready(function() {
        let currentId = null;

        var table = $('#requestsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('requests.index') }}",
                data: function (d) {
                    d.status = $('#statusFilter').val();
                    d.class_section_id = $('#classFilter').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'ticket', name: 'ticket_number' },
                { data: 'applicant', name: 'student.first_name' },
                { data: 'type', name: 'type' },
                { data: 'classe', orderable: false, searchable: false },
                { data: 'deadline', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'status', name: 'status' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            order: [[6, 'desc']]
        });

        $('#statusFilter, #classFilter').change(function() {
            table.draw();
        });

        // Open Modal and populate data
        $(document).on('click', '.update-status', function() {
            currentId = $(this).data('id');
            let presetStatus = $(this).data('status');
            let reqType = $(this).data('type') || '';
            
            $('#modalTicketNo').text($(this).data('ticket'));
            $('#modalStudentName').text($(this).data('student'));
            $('#modalReason').text($(this).data('reason') || LANG.noReason);
            $('#modalDossierWrap').html('<div class="text-center py-3"><i class="fa fa-spinner fa-spin"></i></div>');

            $.get("{{ url('requests') }}/" + currentId + "/dossier", function(res) {
                $('#modalDossierWrap').html(res.html || '');
            });
            
            $('#processForm')[0].reset();
            $('#processForm').data('type', reqType);
            
            if(presetStatus !== 'pending') {
                $('#actionStatus').val(presetStatus);
                $('textarea[name="admin_note"]').val($(this).data('note'));
            } else {
                $('#actionStatus').val('');
            }
            
            $('#actionStatus').trigger('change');
            $('#processModal').modal('show');
        });

        function initPaymentDeadlinePicker() {
            const $el = $('#payment_deadline_input');
            if (!jQuery().bootstrapMaterialDatePicker || !$el.length || !$('#paymentDeadlineDiv').is(':visible')) {
                return;
            }
            if ($el.data('plugin_bootstrapMaterialDatePicker')) {
                $el.bootstrapMaterialDatePicker('destroy');
            }
            $el.bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
                format: 'YYYY-MM-DD',
                triggerEvent: 'click',
                minDate: moment()
            });
        }

        $('#processModal').on('shown.bs.modal', function () {
            setTimeout(initPaymentDeadlinePicker, 100);
        });

        // Show/Hide Partial Days input
        $('#actionStatus').change(function() {
            const val = $(this).val();
            const reqType = $('#processForm').data('type');
            if (val === 'partially_approved') {
                $('#partialDaysDiv').slideDown();
                $('input[name="approved_days"]').prop('required', true);
            } else {
                $('#partialDaysDiv').slideUp();
                $('input[name="approved_days"]').prop('required', false);
            }
            if ((val === 'approved' || val === 'partially_approved') && reqType === 'fee_extension') {
                $('#paymentDeadlineDiv').slideDown(function () {
                    setTimeout(initPaymentDeadlinePicker, 100);
                });
            } else {
                $('#paymentDeadlineDiv').slideUp();
            }
        });

        // Auto-calc payment deadline = today + approved_days
        $(document).on('input change', 'input[name="approved_days"]', function () {
            const days = parseInt($(this).val(), 10);
            if (!days || days < 1) return;
            const deadline = moment().add(days, 'days').format('YYYY-MM-DD');
            if ($('#paymentDeadlineDiv').is(':visible')) {
                $('#payment_deadline_input').val(deadline);
            }
        });

        // Submit AJAX Form
        $('#processForm').submit(function(e) {
            e.preventDefault();
            let btn = $('#saveBtn');
            let originalText = btn.html();
            
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> ' + LANG.processing);

            $.ajax({
                url: "/requests/update-status/" + currentId, 
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    $('#processModal').modal('hide');
                    table.draw(false);
                    
                    Swal.fire({
                        icon: 'success',
                        title: LANG.processed,
                        text: response.message,
                        timer: 2500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    let msg = LANG.errorOccurred;
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire(LANG.error, msg, 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Handle generic deletes
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: LANG.areYouSure,
                text: LANG.cannotRevert,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: LANG.yesDelete
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/requests/" + id,
                        type: 'DELETE',
                        data: { _token: "{{ csrf_token() }}" },
                        success: function(res) {
                            table.ajax.reload();
                            Swal.fire(LANG.deleted, res.message, 'success');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection