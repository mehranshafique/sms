@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('sms_template.page_title') ?? 'SMS Templates' }}</h4>
                    <p class="mb-0">{{ __('sms_template.subtitle') ?? 'Manage automated message templates' }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0">
                        <h4 class="card-title text-primary">{{ __('sms_template.template_list') ?? 'Template List' }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="templateTable" class="display table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('sms_template.event_name') ?? 'Event Name' }}</th>
                                        <th>{{ __('sms_template.message_body') ?? 'Message Body' }}</th>
                                        <th>{{ __('sms_template.tags') ?? 'Available Tags' }}</th>
                                        <th class="text-center">{{ __('sms_template.status') ?? 'Status' }}</th>
                                        <th class="text-end">{{ __('sms_template.actions') ?? 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Template Modal -->
        <div class="modal fade" id="editTemplateModal">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('sms_template.edit_template') ?? 'Edit Template' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editTemplateForm">
                        @csrf
                        <div class="modal-body">
                            <!-- FIXED: Hidden fields ensure disabled input data is successfully serialized and sent -->
                            <input type="hidden" name="event_key" id="templateEventKeyHidden">
                            <input type="hidden" name="name" id="templateNameHidden">
                            <input type="hidden" name="available_tags" id="templateTagsHidden">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('sms_template.event_name') ?? 'Event Name' }}</label>
                                    <input type="text" id="templateName" class="form-control" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ __('sms_template.event_key') ?? 'Event Key' }}</label>
                                    <input type="text" id="templateEventKey" class="form-control" disabled>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">{{ __('sms_template.message_body') ?? 'Message Body' }} <span class="text-danger">*</span></label>
                                    <textarea name="body" id="templateBody" class="form-control" rows="5" required></textarea>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted" id="charCount">0 {{ __('sms_template.characters') ?? 'characters' }}</small>
                                        <small class="text-primary fw-bold" id="smsCount">0 {{ __('sms_template.segments') ?? 'SMS' }}</small>
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">{{ __('sms_template.available_tags') ?? 'Available Tags' }}</label>
                                    <div class="p-3 bg-light border rounded mb-2">
                                        <code id="templateTags" class="text-dark"></code>
                                    </div>
                                    <div id="tagPicker" class="d-flex flex-wrap gap-1"></div>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <div class="form-check form-switch custom-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="templateIsActive" value="1">
                                        <label class="form-check-label fw-bold ms-2 mt-1" style="cursor: pointer;" for="templateIsActive">{{ __('sms_template.is_active') ?? 'Active (Enable this notification)' }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">{{ __('sms_template.cancel') ?? 'Cancel' }}</button>
                            <button type="submit" class="btn btn-primary" id="saveTemplateBtn"><i class="fa fa-save me-2"></i> {{ __('sms_template.save_changes') ?? 'Save Changes' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const TAG_REGISTRY = @json($variableRegistry ?? []);
</script>
<script>
    $(document).ready(function() {
        var table = $('#templateTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('sms_templates.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'body', name: 'body' },
                { data: 'available_tags', name: 'available_tags' },
                { data: 'is_active', name: 'is_active', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ]
        });

        // Open Modal and Populate Data
        $(document).on('click', '.edit-template', function() {
            let key = $(this).data('key');
            let name = $(this).data('name');
            let body = $(this).data('body');
            let tags = (TAG_REGISTRY[key] || []).map(function(t) { return '$' + t; }).join(', ') || $(this).data('tags');

            // Populate Visible (Disabled) Fields
            $('#templateEventKey').val(key);
            $('#templateName').val(name);
            $('#templateTags').text(tags);

            // Populate Hidden Fields (These get serialized for POST)
            $('#templateEventKeyHidden').val(key);
            $('#templateNameHidden').val(name);
            $('#templateTagsHidden').val(tags);

            const picker = $('#tagPicker').empty();
            const eventTags = TAG_REGISTRY[key] || [];
            eventTags.forEach(function(tag) {
                const btn = $('<button type="button" class="btn btn-xs btn-outline-primary btn-sm"></button>').text('$' + tag);
                btn.on('click', function() {
                    const ta = $('#templateBody');
                    ta.val(ta.val() + '$' + tag);
                    ta.trigger('input');
                });
                picker.append(btn);
            });

            // Populate Editable Fields
            $('#templateBody').val(body);
            
            // Determine Active Checkbox Status natively from the row HTML
            let isActive = $(this).closest('tr').find('.badge-danger').length === 0;
            $('#templateIsActive').prop('checked', isActive);

            $('#templateBody').trigger('input');
            $('#editTemplateModal').modal('show');
        });

        // Submit Form via AJAX
        $('#editTemplateForm').submit(function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = $('#saveTemplateBtn');
            let originalText = btn.html();

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> {{ __('sms_template.saving') ?? "Saving..." }}');

            $.ajax({
                url: "{{ route('sms_templates.override') }}",
                type: 'POST',
                data: form.serialize(),
                success: function(res) {
                    $('#editTemplateModal').modal('hide');
                    table.ajax.reload(null, false); // Reload DataTables gently
                    
                    // FIXED: Re-enabled Confirm Button for UI clarity
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('sms_template.success_saved') ?? "Saved!" }}',
                        text: res.message || 'Template updated successfully.',
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6'
                    });

                    btn.prop('disabled', false).html(originalText);
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    
                    // Enhanced Error Reporting for Validation Failures
                    let msg = '{{ __('sms_template.error_occurred') ?? "An error occurred." }}';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors)[0][0]; // Show first strict validation error
                        } else if (xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg,
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });

        // Character Counter Logic
        $('#templateBody').on('input', function() {
            let len = $(this).val().length;
            let sms = Math.ceil(len / 160);
            if (len === 0) sms = 0;
            $('#charCount').text(len + ' {{ __('sms_template.characters') ?? "characters" }}');
            $('#smsCount').text(sms + ' {{ __('sms_template.segments') ?? "SMS" }}');
        });
    });
</script>
@endsection