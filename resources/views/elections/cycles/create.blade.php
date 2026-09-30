@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-3">
            <div class="col-sm-8 p-0">
                <h4>{{ __('voting.create_cycle') }}</h4>
            </div>
            <div class="col-sm-4 p-0 text-sm-end">
                <a href="{{ route('electoral-cycles.index') }}">{{ __('voting.back_to_cycles') }}</a>
            </div>
        </div>

        <form id="cycleForm" method="POST" action="{{ route('electoral-cycles.store') }}">
            @csrf
            <div class="card">
                <div class="card-body row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('voting.title') }} *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('voting.academic_session') }}</label>
                        <select name="academic_session_id" class="form-control">
                            <option value="">—</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('voting.start_date') }}</label>
                        <input type="datetime-local" name="start_date" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('voting.end_date') }}</label>
                        <input type="datetime-local" name="end_date" class="form-control">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">{{ __('voting.description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">{{ __('voting.save_cycle') }}</button>
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
$('#cycleForm').on('submit', function (e) {
    e.preventDefault();
    $.post($(this).attr('action'), $(this).serialize())
        .done(res => Swal.fire({icon:'success', text: res.message}).then(() => location.href = res.redirect))
        .fail(xhr => Swal.fire({icon:'error', text: xhr.responseJSON?.message || '{{ __("voting.system_error") }}'}));
});
</script>
@endsection
