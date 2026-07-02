@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('email_template.page_title') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('email_template.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius:15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-transparent">
                        <h4 class="card-title text-primary fw-bold mb-0">{{ __('email_template.template_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive digitex-dt-wrap">
                            <table id="emailTemplatesTable" class="display table table-striped table-hover mb-0" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('email_template.event_name') }}</th>
                                        <th>{{ __('email_template.subject') }}</th>
                                        <th>{{ __('email_template.body') }}</th>
                                        <th>{{ __('email_template.available_tags') }}</th>
                                        <th class="text-center">{{ __('email_template.active') }}</th>
                                        <th class="text-end">{{ __('email_template.actions') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editEmailModal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="editEmailForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">{{ __('email_template.edit') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="event_key" id="email_event_key">
                <input type="hidden" name="name" id="email_name">
                <input type="hidden" name="available_tags" id="email_tags_hidden">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">{{ __('email_template.event_name') }}</label>
                        <input type="text" id="email_name_display" class="form-control" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">{{ __('email_template.event_key') }}</label>
                        <input type="text" id="email_key_display" class="form-control" disabled>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">{{ __('email_template.subject') }} <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="email_subject" class="form-control" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">{{ __('email_template.body') }} <span class="text-danger">*</span></label>
                        <textarea name="body" id="email_body" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">{{ __('email_template.available_tags') }}</label>
                        <div class="p-3 bg-light border rounded mb-2">
                            <code id="email_tags" class="text-dark"></code>
                        </div>
                        <div id="emailTagPicker" class="d-flex flex-wrap gap-1"></div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check form-switch custom-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="emailIsActive" value="1" checked>
                            <label class="form-check-label fw-bold ms-2 mt-1" style="cursor: pointer;" for="emailIsActive">{{ __('email_template.is_active') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">{{ __('email_template.cancel') }}</button>
                <button type="submit" class="btn btn-primary" id="saveEmailBtn"><i class="fa fa-save me-2"></i> {{ __('email_template.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const TAG_REGISTRY = @json($registry ?? []);
</script>
<script>
$(function(){
    const table = $('#emailTemplatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('email_templates.index') }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'subject', name: 'subject' },
            { data: 'body', name: 'body' },
            { data: 'available_tags', name: 'available_tags' },
            { data: 'is_active', name: 'is_active', className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' }
        ]
    });

    $(document).on('click', '.edit-email-template', function(){
        const key = $(this).data('key');
        const name = $(this).data('name');
        const subject = $(this).data('subject');
        const body = $(this).data('body');
        const tags = (TAG_REGISTRY[key] || []).map(function(t) { return '$' + t; }).join(', ') || $(this).data('tags');

        $('#email_event_key').val(key);
        $('#email_name').val(name);
        $('#email_name_display').val(name);
        $('#email_key_display').val(key);
        $('#email_subject').val(subject);
        $('#email_body').val(body);
        $('#email_tags').text(tags);
        $('#email_tags_hidden').val(tags);

        const picker = $('#emailTagPicker').empty();
        (TAG_REGISTRY[key] || []).forEach(function(tag) {
            const btn = $('<button type="button" class="btn btn-xs btn-outline-primary btn-sm"></button>').text('$' + tag);
            btn.on('click', function() {
                const ta = $('#email_body');
                ta.val(ta.val() + '$' + tag);
            });
            picker.append(btn);
        });

        const isActive = $(this).closest('tr').find('.badge-danger').length === 0;
        $('#emailIsActive').prop('checked', isActive);
        $('#editEmailModal').modal('show');
    });

    $('#editEmailForm').submit(function(e){
        e.preventDefault();
        const btn = $('#saveEmailBtn');
        const original = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> {{ __('email_template.saving') }}');

        $.post('{{ route('email_templates.override') }}', $(this).serialize(), function(res){
            $('#editEmailModal').modal('hide');
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: @json(__('email_template.saved')), text: res.message || '' });
        }).fail(function(xhr){
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error' });
        }).always(function(){
            btn.prop('disabled', false).html(original);
        });
    });
});
</script>
@endsection
