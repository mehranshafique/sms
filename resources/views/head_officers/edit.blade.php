@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('head_officers.edit_head_officer') }}</h4>
                    <p class="mb-0">{{ __('head_officers.manage_list_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('header-officers.index') }}">{{ __('head_officers.officer_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('head_officers.edit_head_officer') }}</a></li>
                </ol>
            </div>
        </div>

        @include('head_officers._form', ['head_officer' => $head_officer])
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        // FIX: Use 'selectpicker' instead of 'select2'
        if(jQuery().selectpicker) {
            $('.multi-select').selectpicker({
                noneSelectedText: "{{ __('head_officers.select_institutes') }}",
                liveSearch: true,
                width: '100%'
            });
        }

        $('#officerForm').submit(function(e){
            e.preventDefault();
            
            // UI Feedback
            let $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

            let formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                type: "POST", // _method handled inside form for PUT
                data: formData,
                processData: false,
                contentType: false,
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("head_officers.success") }}',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    $btn.prop('disabled', false).text('Update'); // Reset button
                    
                    let msg = '{{ __("head_officers.error_occurred") }}';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    if(xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("head_officers.validation_error") }}',
                        html: msg
                    });
                }
            });
        });
    });
</script>
@endsection