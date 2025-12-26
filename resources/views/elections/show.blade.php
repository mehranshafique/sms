@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ $election->title }}</h4>
                    <p class="mb-0 text-muted">{{ __('voting.status') }}: {{ ucfirst($election->status) }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('elections.index') }}">{{ __('voting.election_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('voting.manage') }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            {{-- POSITIONS & CANDIDATES --}}
            <div class="col-xl-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between">
                        <h4 class="card-title">{{ __('voting.ballot_structure') }}</h4>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                            <i class="fa fa-plus"></i> {{ __('voting.add_position') }}
                        </button>
                    </div>
                    <div class="card-body">
                        @forelse($election->positions as $position)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 text-primary">{{ $position->name }}</h5>
                                    <button class="btn btn-xs btn-primary" onclick="openCandidateModal({{ $position->id }})">
                                        <i class="fa fa-user-plus"></i> {{ __('voting.add_candidate') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('voting.candidate_name') }}</th>
                                                <th>{{ __('voting.class') }}</th>
                                                <th>{{ __('voting.status') }}</th>
                                                <th class="text-end">{{ __('voting.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($position->candidates as $candidate)
                                                <tr>
                                                    <td>{{ $candidate->student->first_name }} {{ $candidate->student->last_name }}</td>
                                                    <td>{{ $candidate->student->classSection->name ?? '-' }}</td>
                                                    <td><span class="badge badge-success badge-xs">{{ $candidate->status }}</span></td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-danger shadow btn-xs sharp delete-candidate-btn" data-id="{{ $candidate->id }}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="text-center text-muted">{{ __('voting.no_candidates') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fa fa-list-alt fa-3x mb-3"></i>
                                <p>{{ __('voting.no_positions') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            
            {{-- SIDEBAR STATS --}}
            <div class="col-xl-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('voting.event_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="mb-0">{{ __('voting.start_date') }}</span> <strong>{{ $election->start_date->format('M d, H:i') }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="mb-0">{{ __('voting.end_date') }}</span> <strong>{{ $election->end_date->format('M d, H:i') }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="mb-0">{{ __('voting.candidates_count') }}</span> <strong>{{ $election->candidates->count() }}</strong>
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        @if($election->status !== 'published' && $election->status !== 'completed')
                        <button type="button" class="btn btn-success w-100 mb-2" id="publishBtn" data-id="{{ $election->id }}">{{ __('voting.publish') }}</button>
                        @endif
                        
                        @if($election->status !== 'completed')
                        <button type="button" class="btn btn-secondary w-100" id="closeBtn" data-id="{{ $election->id }}">{{ __('voting.close') }}</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}
<!-- Add Position Modal -->
<div class="modal fade" id="addPositionModal">
    <div class="modal-dialog">
        <form action="{{ route('elections.addPosition', $election->id) }}" method="POST" class="ajax-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('voting.add_position') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('voting.position_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group mt-2">
                        <label>{{ __('voting.sequence') }}</label>
                        <input type="number" name="sequence" class="form-control" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('voting.save_position') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Candidate Modal -->
<div class="modal fade" id="addCandidateModal">
    <div class="modal-dialog">
        <form action="{{ route('elections.addCandidate', $election->id) }}" method="POST" class="ajax-form">
            @csrf
            <input type="hidden" name="election_position_id" id="modalPositionId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('voting.add_candidate') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label>{{ __('voting.admission_number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="admission_number" class="form-control" placeholder="{{ __('voting.admission_number') }}" required>
                        <small class="text-muted">Enter student admission ID to search</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('voting.add_candidate') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function openCandidateModal(positionId) {
        document.getElementById('modalPositionId').value = positionId;
        new bootstrap.Modal(document.getElementById('addCandidateModal')).show();
    }

    $(document).ready(function(){
        // Generic Ajax Form Handler
        $('.ajax-form').submit(function(e){
            e.preventDefault();
            var $form = $(this);
            var $modal = $form.closest('.modal');
            
            $.ajax({
                url: $form.attr('action'),
                type: "POST",
                data: $form.serialize(),
                success: function(response){
                    if($modal.length) {
                        bootstrap.Modal.getInstance($modal[0]).hide();
                    }
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("voting.success") }}',
                        text: response.message
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr){
                    let msg = '{{ __("voting.system_error") }}';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: '{{ __("voting.validation_error") }}', html: msg });
                }
            });
        });

        // Delete Candidate Logic
        $(document).on('click', '.delete-candidate-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('elections.destroyCandidate', ':id') }}".replace(':id', id);
            
            Swal.fire({
                title: "{{ __('voting.delete_warning') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('voting.yes_delete') }}",
                cancelButtonText: "{{ __('voting.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('voting.success') }}", response.message, 'success');
                            window.location.reload();
                        },
                        error: function() {
                            Swal.fire("{{ __('voting.error') }}", "{{ __('voting.system_error') }}", 'error');
                        }
                    });
                }
            });
        });

        // Publish Election Logic
        $('#publishBtn').click(function() {
            let id = $(this).data('id');
            let url = "{{ route('elections.publish', ':id') }}".replace(':id', id);

            Swal.fire({
                title: "{{ __('voting.confirm_publish') }}",
                text: "This will make the election visible to voters.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: "Yes, Publish",
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('voting.success') }}", response.message, 'success')
                                .then(() => window.location.reload());
                        },
                        error: function() {
                            Swal.fire("{{ __('voting.error') }}", "{{ __('voting.system_error') }}", 'error');
                        }
                    });
                }
            });
        });

        // Close Election Logic
        $('#closeBtn').click(function() {
            let id = $(this).data('id');
            let url = "{{ route('elections.close', ':id') }}".replace(':id', id);

            Swal.fire({
                title: "{{ __('voting.confirm_close') }}",
                text: "Voting will be stopped immediately.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "Yes, Close Election",
                confirmButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                        success: function(response) {
                            Swal.fire("{{ __('voting.success') }}", response.message, 'success')
                                .then(() => window.location.reload());
                        },
                        error: function() {
                            Swal.fire("{{ __('voting.error') }}", "{{ __('voting.system_error') }}", 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection