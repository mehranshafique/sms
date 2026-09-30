@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="text-center mb-4">
            <h4>{{ $election->title }}</h4>
            <p class="text-muted">{{ __('voting.cast_your_vote') }}</p>
        </div>

        @if($hasVoted)
            <div class="alert alert-success text-center">
                {{ __('voting.participation_recorded') }}
                <div class="small mt-1">{{ __('voting.secrecy_receipt') }}</div>
            </div>
            <div class="text-center">
                <a href="{{ route('student.elections.index') }}" class="btn btn-secondary">{{ __('voting.back_to_dashboard') }}</a>
            </div>
        @else
            <form id="ballotForm">
                @foreach($election->positions as $position)
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 text-primary">{{ $position->name }}</h5>
                        <div class="row">
                            @foreach($position->candidates as $candidate)
                                <div class="col-md-4 col-lg-3 mb-3">
                                    <label class="card h-100 p-3 text-center" style="cursor:pointer;">
                                        <img src="{{ $candidate->photoUrl() }}" class="rounded-circle mx-auto mb-2" style="width:100px;height:100px;object-fit:cover;" alt="">
                                        <div class="fw-bold">{{ $candidate->displayName() }}</div>
                                        <div class="small text-muted mb-2">{{ $candidate->classLabel() }}</div>
                                        <input type="radio" name="choices[{{ $position->id }}]" value="{{ $candidate->id }}" required>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="text-center mb-5">
                    <button type="submit" class="btn btn-primary btn-lg">{{ __('voting.submit_ballot') }}</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$('#ballotForm').on('submit', function (e) {
    e.preventDefault();
    const choices = {};
    $(this).find('input[type=radio]:checked').each(function () {
        const name = $(this).attr('name'); // choices[12]
        const match = name.match(/\[(\d+)\]/);
        if (match) choices[match[1]] = parseInt($(this).val(), 10);
    });
    Swal.fire({
        title: @json(__('voting.confirm_vote')),
        text: @json(__('voting.vote_warning')),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: @json(__('voting.yes_vote'))
    }).then(r => {
        if (!r.isConfirmed) return;
        $.ajax({
            url: @json(route('student.elections.vote', $election)),
            method: 'POST',
            data: { _token: @json(csrf_token()), choices },
            success: (res) => Swal.fire({icon:'success', text: res.message}).then(() => location.reload()),
            error: (xhr) => Swal.fire({icon:'error', text: xhr.responseJSON?.message || @json(__('voting.system_error'))})
        });
    });
});
</script>
@endsection
