@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-3 p-3 bg-white rounded shadow-sm">
            <div class="col-md-8">
                <h4 class="mb-1">{{ $cycle->title }}</h4>
                <p class="mb-0 text-muted">{{ $cycle->statusLabel() }} · {{ __('voting.elections_count') }}: {{ $cycle->elections->count() }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('electoral-cycles.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('voting.back_to_cycles') }}</a>
                @can('election.create')
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addElectionModal">{{ __('voting.add_election') }}</button>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('voting.title') }}</th>
                            <th>{{ __('voting.status') }}</th>
                            <th>{{ __('voting.eligibility') }}</th>
                            <th>{{ __('voting.candidates_count') }}</th>
                            <th>{{ __('voting.voters_count') }}</th>
                            <th>{{ __('voting.voted_count') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycle->elections as $election)
                            <tr>
                                <td>{{ $election->title }}</td>
                                <td><span class="badge badge-primary light">{{ $election->statusLabel() }}</span></td>
                                <td>{{ $election->eligibilityLabel() }}</td>
                                <td>{{ $election->candidates_count }}</td>
                                <td>{{ $election->voters_count }}</td>
                                <td>{{ $election->participations_count }}</td>
                                <td class="text-end">
                                    <a class="btn btn-xs btn-info" href="{{ route('elections.show', $election) }}">{{ __('voting.manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">{{ __('voting.no_elections_in_cycle') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@can('election.create')
<div class="modal fade" id="addElectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="addElectionForm" method="POST" action="{{ route('electoral-cycles.elections.store', $cycle) }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('voting.add_election') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ __('voting.title') }} *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ __('voting.academic_session') }}</label>
                    <select name="academic_session_id" class="form-control">
                        <option value="{{ $cycle->academic_session_id }}">{{ __('voting.use_cycle_session') }}</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ __('voting.start_date') }} *</label>
                    <input type="datetime-local" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ __('voting.end_date') }} *</label>
                    <input type="datetime-local" name="end_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ __('voting.eligibility') }} *</label>
                    <select name="eligibility_type" class="form-control" id="eligType">
                        <option value="all_students">{{ __('voting.eligibility_all_students') }}</option>
                        <option value="grade_levels">{{ __('voting.eligibility_grade_levels') }}</option>
                        <option value="class_sections">{{ __('voting.eligibility_class_sections') }}</option>
                        <option value="departments">{{ __('voting.eligibility_departments') }}</option>
                        <option value="manual_list">{{ __('voting.eligibility_manual_list') }}</option>
                    </select>
                </div>
                <div class="col-12 mb-2">
                    <label class="form-label">{{ __('voting.description') }}</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('voting.cancel') }}</button>
                <button class="btn btn-primary">{{ __('voting.save_election') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$('#addElectionForm').on('submit', function (e) {
    e.preventDefault();
    $.post($(this).attr('action'), $(this).serialize())
        .done(res => location.href = res.redirect)
        .fail(xhr => Swal.fire({icon:'error', text: xhr.responseJSON?.message || '{{ __("voting.system_error") }}'}));
});
</script>
@endsection
