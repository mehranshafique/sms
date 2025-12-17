@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('exam.add_exam') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">{{ __('exam.exam_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('exam.create_new') }}</a></li>
                </ol>
            </div>
        </div>

        @include('exams._form')
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        // Initialize Select2 for all select elements in the form
        $('.default-select').select2();

        $('#examForm').submit(function(e){
            e.preventDefault();
            
            // 1. Prevent Double Submission
            let $submitBtn = $(this).find('button[type="submit"]');
            let originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

            let formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("exam.success") }}',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    // 2. Re-enable button on error
                    $submitBtn.prop('disabled', false).html(originalText);

                    let msg = '{{ __("exam.error_occurred") }}';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    if(xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("exam.validation_error") }}',
                        html: msg
                    });
                }
            });
        });
    });
</script>
@endsection