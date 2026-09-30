@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <h4 class="text-primary fw-bold mb-1">{{ __('voting.cycles_title') }}</h4>
                <p class="mb-0 text-muted">{{ __('voting.cycles_subtitle') }}</p>
            </div>
            <div class="col-sm-6 p-0 d-flex justify-content-sm-end">
                @can('election.create')
                <a href="{{ route('electoral-cycles.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> {{ __('voting.create_cycle') }}
                </a>
                @endcan
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="cycleTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('voting.title') }}</th>
                                <th>{{ __('voting.status') }}</th>
                                <th>{{ __('voting.elections_count') }}</th>
                                <th class="text-end">{{ __('voting.actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#cycleTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: @json(route('electoral-cycles.index')),
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'title', name: 'title' },
            { data: 'status', name: 'status' },
            { data: 'elections_count', name: 'elections_count', searchable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' }
        ]
    });
});
</script>
@endsection
