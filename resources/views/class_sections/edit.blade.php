@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('class_section.edit_class') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('class-sections.index') }}">{{ __('class_section.class_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('class_section.edit_class') }}</a></li>
                </ol>
            </div>
        </div>

        @include('class_sections._form', ['class_section' => $class_section])
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        $('#classForm').submit(function(e){
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: "POST", // Form contains _method for PUT
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("class_section.success") }}',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    let msg = '{{ __("class_section.error_occurred") }}';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: '{{ __("class_section.validation_error") }}', html: msg });
                }
            });
        });
    });
</script>
@endsection