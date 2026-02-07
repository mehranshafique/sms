@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('sms_template.page_title') }}</h4>
                    <p class="mb-0">{{ __('sms_template.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0">
                        <h4 class="card-title text-primary">{{ __('sms_template.template_list') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="templateTable" class="display table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('sms_template.event_name') }}</th>
                                        <th>{{ __('sms_template.message_body') }}</th>
                                        <th>{{ __('sms_template.tags') }}</th>
                                        <th>{{ __('sms_template.status') }}</th>
                                        <th class="text-end">{{ __('sms_template.action') }}</th>
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

{{-- Edit Modal --}}
<div class="modal fade" id="editTemplateModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('sms_template.edit_template') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('sms_templates.override') }}" method="POST" id="templateForm">
                @csrf
                <input type="hidden" name="event_key" id="eventKey">
                <input type="hidden" name="name" id="eventName">
                <input type="hidden" name="available_tags" id="eventTagsHidden">
                
                <div class="modal-body">
                    <div class="alert alert-info py-2 fs-13">
                        <i class="fa fa-info-circle me-1"></i> {{ __('sms_template.customize_help') }}
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('sms_template.available_tags_label') }}</label>
                        <div id="tagsDisplay" class="p-2 bg-light border rounded text-primary small font-monospace"></div>
                        <small class="text-muted">{{ __('sms_template.click_to_copy') }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('sms_template.body_label') }} <span class="text-danger">*</span></label>
                        <textarea name="body" id="templateBody" class="form-control" rows="5" required></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted" id="charCount">0 {{ __('sms_template.characters') }}</small>
                            <small class="text-muted" id="smsCount">0 {{ __('sms_template.segments') }}</small>
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                        <label class="form-check-label" for="isActive">{{ __('sms_template.active_label') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('sms_template.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('sms_template.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        var table = $('#templateTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('sms_templates.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name', className: 'fw-bold' },
                { data: 'body', name: 'body', render: (data) => data.length > 50 ? data.substr(0, 50) + '...' : data },
                { data: 'available_tags', name: 'available_tags', className: 'small text-muted font-monospace' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                emptyTable: "{{ __('finance.no_records_found') }}" // Fallback or add to sms_template
            }
        });

        // Edit Handler
        $(document).on('click', '.edit-template', function() {
            let btn = $(this);
            $('#eventKey').val(btn.data('key'));
            $('#eventName').val(btn.data('name'));
            $('#templateBody').val(btn.data('body'));
            $('#tagsDisplay').text(btn.data('tags'));
            $('#eventTagsHidden').val(btn.data('tags'));
            
            // Trigger char count update
            $('#templateBody').trigger('input');
            
            $('#editTemplateModal').modal('show');
        });

        // Form Submit
        $('#templateForm').submit(function(e) {
            e.preventDefault();
            let btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('{{ __('sms_template.save_changes') }}...'); // Could add localized loading text

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#editTemplateModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('sms_template.success_saved') }}',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    btn.prop('disabled', false).text('{{ __('sms_template.save_changes') }}');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('{{ __('sms_template.save_changes') }}');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON ? xhr.responseJSON.message : '{{ __('sms_template.error_occurred') }}'
                    });
                }
            });
        });

        // Character Counter Logic
        $('#templateBody').on('input', function() {
            let len = $(this).val().length;
            let sms = Math.ceil(len / 160);
            if (len === 0) sms = 0;
            $('#charCount').text(len + ' {{ __('sms_template.characters') }}');
            $('#smsCount').text(sms + ' {{ __('sms_template.segments') }}');
        });
    });
</script>
@endsection