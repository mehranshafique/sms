@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('notice.add_notice') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('notices.index') }}">{{ __('notice.notice_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('notice.add_notice') }}</a></li>
                </ol>
            </div>
        </div>

        @include('notices._form')
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        $('#mainForm').submit(function(e){
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    let msg = 'Error occurred';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Validation Error', html: msg });
                }
            });
        });
    });
</script>
@endsection