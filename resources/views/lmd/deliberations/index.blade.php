@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <h4>{{ __('lmd_deliberation.page_title') }}</h4>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        @if($session)
        <form method="POST" action="{{ route('lmd-deliberations.generate') }}" class="card card-body mb-4">
            @csrf
            <input type="hidden" name="academic_session_id" value="{{ $session->id }}">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label>{{ __('lmd_deliberation.semester') }}</label>
                    <select name="semester" id="semesterFilter" class="form-control">
                        <option value="1" @selected($semester === 1)>1</option>
                        <option value="2" @selected($semester === 2)>2</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary">{{ __('lmd_deliberation.generate') }}</button>
                </div>
            </div>
        </form>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="lmdDeliberationTable" class="display table table-striped table-hover table-bordered mb-0" style="min-width: 845px">
                        <thead>
                            <tr>
                                <th>{{ __('transport.student') }}</th>
                                <th>{{ __('lmd_deliberation.average') }}</th>
                                <th>{{ __('state_exam.mention') }}</th>
                                <th>{{ __('lmd_deliberation.decision') }}</th>
                                <th>{{ __('state_exam.status') }}</th>
                                <th class="text-end">{{ __('lmd_deliberation.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deliberations as $d)
                                <tr>
                                    <td>{{ $d->student->full_name ?? '—' }}</td>
                                    <td>{{ $d->average }}/20</td>
                                    <td>{{ $d->mention }}</td>
                                    <td>{{ __('lmd_deliberation.decision_' . $d->decision) }}</td>
                                    <td>{{ $d->status === 'validated' ? __('lmd_deliberation.status_validated') : __('lmd_deliberation.status_draft') }}</td>
                                    <td class="text-end">
                                        @if($d->status !== 'validated')
                                            <form method="POST" action="{{ route('lmd-deliberations.validate', $d) }}">@csrf
                                                <button class="btn btn-sm btn-success">{{ __('lmd_deliberation.validate') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
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
        $('#lmdDeliberationTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
            columnDefs: [
                { orderable: false, searchable: false, targets: -1 }
            ],
            language: {
                search: @json(__('lmd_deliberation.datatable_search')),
                lengthMenu: @json(__('lmd_deliberation.datatable_length')),
                info: @json(__('lmd_deliberation.datatable_info')),
                infoEmpty: @json(__('lmd_deliberation.datatable_info_empty')),
                zeroRecords: @json(__('lmd_deliberation.no_records_found')),
                emptyTable: @json(__('lmd_deliberation.no_records_found')),
                paginate: {
                    first: @json(__('lmd_deliberation.datatable_first')),
                    last: @json(__('lmd_deliberation.datatable_last')),
                    next: '<i class="fa fa-angle-double-right" aria-hidden="true"></i>',
                    previous: '<i class="fa fa-angle-double-left" aria-hidden="true"></i>'
                }
            }
        });
    });
</script>
@endsection
