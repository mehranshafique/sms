@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <!-- Title -->
        <div class="row page-titles mx-0">
            <div class="col-sm-12 p-md-0">
                <div class="welcome-text text-center">
                    <h4>{{ $election->title }}</h4>
                    <p class="mb-0">{{ __('voting.cast_your_vote') }}</p>
                </div>
            </div>
        </div>

        @foreach($election->positions as $position)
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="text-primary border-bottom pb-2 mb-4">{{ $position->name }}</h3>
                </div>
                
                @php
                    $hasVoted = isset($myVotes[$position->id]);
                @endphp

                @foreach($position->candidates as $candidate)
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                        <div class="card candidate-card {{ $hasVoted && $myVotes[$position->id] == $candidate->id ? 'border-primary shadow' : '' }}">
                            <div class="card-body text-center">
                                <div class="new-arrival-product">
                                    <div class="new-arrivals-img-contnal">
                                        <img class="img-fluid rounded-circle" src="{{ $candidate->student->student_photo ? asset('storage/'.$candidate->student->student_photo) : asset('images/no-image.png') }}" alt="" style="width:120px; height:120px; object-fit:cover;">
                                    </div>
                                    <div class="new-arrival-content text-center mt-3">
                                        <h4>{{ $candidate->student->full_name }}</h4>
                                        <p class="text-muted small">{{ $candidate->student->admission_number }}</p>
                                        
                                        @if($hasVoted)
                                            @if($myVotes[$position->id] == $candidate->id)
                                                <button class="btn btn-success btn-sm mt-2" disabled>
                                                    <i class="fa fa-check"></i> {{ __('voting.voted') }}
                                                </button>
                                            @else
                                                <button class="btn btn-light btn-sm mt-2" disabled>{{ __('voting.vote') }}</button>
                                            @endif
                                        @else
                                            <button class="btn btn-outline-primary btn-sm mt-2 vote-btn" 
                                                data-position="{{ $position->id }}" 
                                                data-candidate="{{ $candidate->id }}">
                                                {{ __('voting.vote') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('.vote-btn').click(function() {
            let btn = $(this);
            let positionId = btn.data('position');
            let candidateId = btn.data('candidate');
            let url = "{{ route('student.elections.vote', $election->id) }}";

            Swal.fire({
                title: "{{ __('voting.confirm_vote') }}",
                text: "{{ __('voting.vote_warning') }}",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: "{{ __('voting.yes_vote') }}",
                cancelButtonText: "{{ __('voting.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            position_id: positionId,
                            candidate_id: candidateId
                        },
                        success: function(response) {
                            Swal.fire("{{ __('voting.success') }}", response.message, 'success')
                                .then(() => window.location.reload());
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON.message || "{{ __('voting.error') }}";
                            Swal.fire("{{ __('voting.error') }}", msg, 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection