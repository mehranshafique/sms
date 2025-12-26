@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('voting.create_election') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('elections.index') }}">{{ __('voting.election_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('voting.create_election') }}</a></li>
                </ol>
            </div>
        </div>

        <form action="{{ route('elections.store') }}" method="POST" id="electionForm">
            @csrf
            <div class="row">
                <div class="col-xl-12">
                    <div class="card shadow-sm" style="border-radius: 15px;">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('voting.title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('voting.academic_session') }} <span class="text-danger">*</span></label>
                                    <select name="academic_session_id" class="form-control default-select" required>
                                        @foreach($sessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->name }} ({{ $session->year }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('voting.start_date') }} <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="start_date" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('voting.end_date') }} <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="end_date" class="form-control" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('voting.description') }}</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">{{ __('voting.save_election') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        $('#electionForm').submit(function(e){
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("voting.success") }}',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    let msg = '{{ __("voting.system_error") }}';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: '{{ __("voting.validation_error") }}', html: msg });
                }
            });
        });
    });
</script>
@endsection