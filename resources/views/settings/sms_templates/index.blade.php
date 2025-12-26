@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>SMS Templates</h4>
                    <p class="mb-0">Customize your notification messages</p>
                </div>
            </div>
        </div>

        <div class="row">
            @foreach($templates as $template)
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{ $template->name }}</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input toggle-status" type="checkbox" data-id="{{ $template->id }}" {{ $template->is_active ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="template-form" action="{{ route('sms_templates.update', $template->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_active" value="{{ $template->is_active ? 1 : 0 }}" class="status-input">
                            
                            <div class="mb-3">
                                <label class="form-label">Message Body</label>
                                <textarea name="body" class="form-control" rows="4">{{ $template->body }}</textarea>
                                <small class="text-muted d-block mt-2">
                                    <strong>Tags:</strong> {{ $template->available_tags ?? 'None' }}
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        // Toggle Switch Logic
        $('.toggle-status').change(function(){
            let isActive = $(this).is(':checked') ? 1 : 0;
            $(this).closest('.card').find('.status-input').val(isActive);
        });

        // Form Submit
        $('.template-form').submit(function(e){
            e.preventDefault();
            let $form = $(this);
            
            $.ajax({
                url: $form.attr('action'),
                type: "POST", // Method spoofing used inside form
                data: $form.serialize(),
                success: function(response){
                    Swal.fire('Saved', response.message, 'success');
                },
                error: function(){
                    Swal.fire('Error', 'Could not update template', 'error');
                }
            });
        });
    });
</script>
@endsection