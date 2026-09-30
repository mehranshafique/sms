@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row page-titles mx-0 mb-3 p-3 bg-white rounded shadow-sm">
            <div class="col-md-7">
                <h4 class="mb-1">{{ $election->title }}</h4>
                <p class="mb-0 text-muted">
                    @if($election->cycle)
                        <a href="{{ route('electoral-cycles.show', $election->cycle) }}">{{ $election->cycle->title }}</a> ·
                    @endif
                    {{ $election->statusLabel() }} · {{ $election->eligibilityLabel() }}
                </p>
            </div>
            <div class="col-md-5 text-md-end">
                @if(in_array($election->status, ['draft','scheduled'], true))
                    <button type="button" class="btn btn-success btn-sm status-btn" data-url="{{ route('elections.open', $election) }}">{{ __('voting.open_voting') }}</button>
                @endif
                @if($election->status === 'open')
                    <button type="button" class="btn btn-dark btn-sm status-btn" data-url="{{ route('elections.close', $election) }}">{{ __('voting.close') }}</button>
                @endif
                @if($election->status === 'closed')
                    <button type="button" class="btn btn-primary btn-sm status-btn" data-url="{{ route('elections.publish-results', $election) }}">{{ __('voting.publish_results') }}</button>
                @endif
            </div>
        </div>

        <ul class="nav nav-tabs mb-3">
            @foreach(['settings' => __('voting.tab_settings'), 'ballot' => __('voting.tab_ballot'), 'voters' => __('voting.tab_voters'), 'monitoring' => __('voting.tab_monitoring'), 'results' => __('voting.tab_results')] as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('elections.show', [$election, 'tab' => $key]) }}">{{ $label }}</a>
                </li>
            @endforeach
        </ul>

        @if($tab === 'settings')
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('elections.settings', $election) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('voting.title') }}</label>
                            <input type="text" name="title" class="form-control" value="{{ $election->title }}" @disabled(!in_array($election->status, ['draft','scheduled']))>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('voting.start_date') }}</label>
                            <input type="datetime-local" name="start_date" class="form-control" value="{{ $election->start_date?->format('Y-m-d\TH:i') }}" @disabled(!in_array($election->status, ['draft','scheduled']))>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('voting.end_date') }}</label>
                            <input type="datetime-local" name="end_date" class="form-control" value="{{ $election->end_date?->format('Y-m-d\TH:i') }}" @disabled(!in_array($election->status, ['draft','scheduled']))>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('voting.eligibility') }}</label>
                            <select name="eligibility_type" class="form-control" id="eligType" @disabled(!in_array($election->status, ['draft','scheduled']))>
                                @foreach(['all_students','grade_levels','class_sections','departments','manual_list'] as $t)
                                    <option value="{{ $t }}" @selected($election->eligibility_type === $t)>{{ __('voting.eligibility_'.$t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 mb-3" id="eligIdsWrap">
                            <label class="form-label">{{ __('voting.eligibility_ids') }}</label>
                            <select name="eligibility_ids[]" class="form-control" multiple size="6" @disabled(!in_array($election->status, ['draft','scheduled']))>
                                @foreach($grades as $g)
                                    <option value="{{ $g->id }}" data-type="grade_levels" @selected(in_array($g->id, $election->eligibility_ids ?? []))>{{ $g->name }}</option>
                                @endforeach
                                @foreach($sections as $s)
                                    <option value="{{ $s->id }}" data-type="class_sections" @selected(in_array($s->id, $election->eligibility_ids ?? []))>{{ class_section_label($s) }}</option>
                                @endforeach
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" data-type="departments" @selected(in_array($d->id, $election->eligibility_ids ?? []))>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('voting.eligibility_ids_help') }}</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">{{ __('voting.description') }}</label>
                            <textarea name="description" class="form-control" rows="2" @disabled(!in_array($election->status, ['draft','scheduled']))>{{ $election->description }}</textarea>
                        </div>
                        <div class="col-12 mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="results_visible_to_voters" value="1" id="visVoters" @checked($election->results_visible_to_voters) @disabled(!in_array($election->status, ['draft','scheduled']))>
                            <label class="form-check-label" for="visVoters">{{ __('voting.results_visible_to_voters') }}</label>
                        </div>
                        @if(in_array($election->status, ['draft','scheduled'], true))
                        <div class="col-12"><button class="btn btn-primary">{{ __('voting.save_settings') }}</button></div>
                        @endif
                    </div>
                </form>
            </div></div>
        @endif

        @if($tab === 'ballot')
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0">{{ __('voting.ballot_structure') }}</h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">{{ __('voting.add_position') }}</button>
                </div>
                <div class="card-body">
                    @forelse($election->positions as $position)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <h5 class="text-primary mb-0">{{ $position->name }}</h5>
                                <button class="btn btn-xs btn-primary" onclick="openCandidateModal({{ $position->id }})">{{ __('voting.add_candidate') }}</button>
                            </div>
                            <table class="table table-sm mb-0">
                                <thead><tr><th>{{ __('voting.candidate_name') }}</th><th>{{ __('voting.class') }}</th><th></th></tr></thead>
                                <tbody>
                                @forelse($position->candidates as $candidate)
                                    <tr>
                                        <td>{{ $candidate->displayName() }}</td>
                                        <td>{{ $candidate->classLabel() }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-danger btn-xs delete-candidate-btn" data-id="{{ $candidate->id }}"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">{{ __('voting.no_candidates') }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('voting.no_positions') }}</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if($tab === 'voters')
            <div class="row">
                <div class="col-lg-4 mb-3">
                    <div class="card h-100"><div class="card-header"><h5 class="mb-0">{{ __('voting.sync_voters') }}</h5></div><div class="card-body">
                        <p class="small text-muted">{{ __('voting.sync_voters_help') }}</p>
                        <form method="POST" action="{{ route('elections.voters.sync', $election) }}">@csrf
                            <button class="btn btn-primary btn-sm">{{ __('voting.sync_now') }}</button>
                        </form>
                        <hr>
                        <form method="POST" action="{{ route('elections.voters.add', $election) }}" class="mb-3">@csrf
                            <input type="hidden" name="mode" value="digitex">
                            <label class="form-label">{{ __('voting.admission_number') }}</label>
                            <input type="text" name="admission_number" class="form-control mb-2" required>
                            <button class="btn btn-outline-primary btn-sm">{{ __('voting.add_digitex_voter') }}</button>
                        </form>
                        <form method="POST" action="{{ route('elections.voters.add', $election) }}">@csrf
                            <input type="hidden" name="mode" value="external">
                            <label class="form-label">{{ __('voting.external_voter') }}</label>
                            <input type="text" name="display_name" class="form-control mb-2" placeholder="{{ __('voting.candidate_name') }}" required>
                            <input type="text" name="voter_code" class="form-control mb-2" placeholder="{{ __('voting.voter_code') }}">
                            <button class="btn btn-outline-secondary btn-sm">{{ __('voting.add_external_voter') }}</button>
                        </form>
                        <hr>
                        <form method="POST" action="{{ route('elections.voters.import', $election) }}" enctype="multipart/form-data">@csrf
                            <label class="form-label">{{ __('voting.import_csv') }}</label>
                            <input type="file" name="csv_file" class="form-control mb-2" accept=".csv,text/csv" required>
                            <button class="btn btn-dark btn-sm">{{ __('voting.import') }}</button>
                        </form>
                    </div></div>
                </div>
                <div class="col-lg-8 mb-3">
                    <div class="card"><div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">{{ __('voting.voter_list') }} ({{ $election->voters()->count() }})</h5>
                        <a class="btn btn-sm btn-outline-success" href="{{ route('elections.export.turnout', $election) }}">{{ __('voting.export_turnout') }}</a>
                    </div><div class="card-body table-responsive" style="max-height:480px;overflow:auto;">
                        <table class="table table-sm">
                            <thead><tr><th>{{ __('voting.voter_code') }}</th><th>{{ __('voting.candidate_name') }}</th><th>{{ __('voting.source') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($election->voters as $voter)
                                <tr>
                                    <td>{{ $voter->voter_code }}</td>
                                    <td>{{ $voter->displayName() }}</td>
                                    <td>{{ __('voting.source_'.$voter->source) }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('elections.voters.destroy', $voter) }}" class="d-inline" onsubmit="return confirm(@json(__('voting.confirm_delete')))">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-outline-danger">{{ __('voting.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div></div>
                </div>
            </div>
        @endif

        @if($tab === 'monitoring')
            <div class="row mb-3">
                <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('voting.registered') }}</div><h3>{{ $turnout['registered'] }}</h3></div></div></div>
                <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('voting.voted') }}</div><h3 class="text-success">{{ $turnout['voted'] }}</h3></div></div></div>
                <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('voting.not_voted') }}</div><h3 class="text-warning">{{ $turnout['not_voted'] }}</h3></div></div></div>
                <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('voting.turnout') }}</div><h3>{{ $turnout['turnout_percent'] }}%</h3></div></div></div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card"><div class="card-header">{{ __('voting.who_voted') }}</div><div class="card-body" style="max-height:320px;overflow:auto;">
                        <ul class="mb-0">@foreach($lists['voted'] as $v)<li>{{ $v->displayName() }} ({{ $v->voter_code }})</li>@endforeach</ul>
                        <p class="small text-muted mt-2 mb-0">{{ __('voting.secrecy_note') }}</p>
                    </div></div>
                </div>
                <div class="col-md-6">
                    <div class="card"><div class="card-header">{{ __('voting.who_not_voted') }}</div><div class="card-body" style="max-height:320px;overflow:auto;">
                        <ul class="mb-0">@foreach($lists['notVoted'] as $v)<li>{{ $v->displayName() }} ({{ $v->voter_code }})</li>@endforeach</ul>
                    </div></div>
                </div>
            </div>
        @endif

        @if($tab === 'results')
            @if(empty($results))
                <div class="alert alert-info">{{ __('voting.results_after_close') }}</div>
            @else
                <div class="mb-3">
                    <a class="btn btn-sm btn-outline-success" href="{{ route('elections.export.results', $election) }}">{{ __('voting.export_results') }}</a>
                </div>
                @foreach($results as $block)
                    <div class="card mb-3"><div class="card-header"><strong>{{ $block['position_name'] }}</strong> · {{ $block['total_ballots'] }} {{ __('voting.ballots') }}</div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>{{ __('voting.candidate_name') }}</th><th>{{ __('voting.votes') }}</th><th>%</th></tr></thead>
                                <tbody>
                                @foreach($block['candidates'] as $c)
                                    <tr><td>{{ $c['name'] }}</td><td>{{ $c['votes'] }}</td><td>{{ $c['percent'] }}%</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        @endif
    </div>
</div>

<div class="modal fade" id="addPositionModal"><div class="modal-dialog"><form class="modal-content" id="addPositionForm" method="POST" action="{{ route('elections.addPosition', $election) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('voting.add_position') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" name="name" class="form-control" placeholder="{{ __('voting.position_name') }}" required>
        <input type="number" name="sequence" class="form-control mt-2" value="0" min="0">
    </div>
    <div class="modal-footer"><button class="btn btn-primary">{{ __('voting.save_position') }}</button></div>
</form></div></div>

<div class="modal fade" id="addCandidateModal"><div class="modal-dialog"><form class="modal-content" id="addCandidateForm" method="POST" action="{{ route('elections.addCandidate', $election) }}" enctype="multipart/form-data">@csrf
    <input type="hidden" name="election_position_id" id="candPositionId">
    <div class="modal-header"><h5 class="modal-title">{{ __('voting.add_candidate') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <select name="mode" id="candMode" class="form-control mb-2">
            <option value="digitex">{{ __('voting.from_digitex') }}</option>
            <option value="external">{{ __('voting.independent_candidate') }}</option>
        </select>
        <div id="digitexFields">
            <input type="text" name="admission_number" class="form-control" placeholder="{{ __('voting.admission_number') }}">
        </div>
        <div id="externalFields" class="d-none">
            <input type="text" name="external_name" class="form-control mb-2" placeholder="{{ __('voting.candidate_name') }}">
            <input type="text" name="external_class_label" class="form-control mb-2" placeholder="{{ __('voting.class') }}">
            <input type="file" name="external_photo" class="form-control mb-2" accept="image/*">
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">{{ __('voting.save_candidate') }}</button></div>
</form></div></div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openCandidateModal(positionId) {
    $('#candPositionId').val(positionId);
    new bootstrap.Modal(document.getElementById('addCandidateModal')).show();
}
$('#candMode').on('change', function () {
    const ext = $(this).val() === 'external';
    $('#externalFields').toggleClass('d-none', !ext);
    $('#digitexFields').toggleClass('d-none', ext);
});
$('#addPositionForm,#addCandidateForm').on('submit', function (e) {
    e.preventDefault();
    const form = this;
    const fd = new FormData(form);
    $.ajax({ url: form.action, method: 'POST', data: fd, processData: false, contentType: false })
        .done(res => location.href = res.redirect || location.href)
        .fail(xhr => Swal.fire({icon:'error', text: xhr.responseJSON?.message || '{{ __("voting.system_error") }}'}));
});
$('.delete-candidate-btn').on('click', function () {
    const id = $(this).data('id');
    const url = @json(url('elections/candidates')).replace(/\/?$/, '/') + id;
    Swal.fire({title:@json(__('voting.confirm_delete')), icon:'warning', showCancelButton:true}).then(r => {
        if (!r.isConfirmed) return;
        $.ajax({ url, method: 'DELETE', data: {_token: @json(csrf_token())} }).done(() => location.reload());
    });
});
$('.status-btn').on('click', function () {
    const url = $(this).data('url');
    $.post(url, {_token: @json(csrf_token())})
        .done(res => Swal.fire({icon:'success', text: res.message}).then(() => location.reload()))
        .fail(xhr => Swal.fire({icon:'error', text: xhr.responseJSON?.message || '{{ __("voting.system_error") }}'}));
});
</script>
@endsection
