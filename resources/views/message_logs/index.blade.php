@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('message_log.page_title') }}</h4>
                    <p class="mb-0">{{ __('message_log.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('message_log.stat_sent_7d') }}</div>
                        <h3 class="mb-0 text-success">{{ $stats['sent'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('message_log.stat_failed_7d') }}</div>
                        <h3 class="mb-0 text-danger">{{ $stats['failed'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">SMS (7d)</div>
                        <h3 class="mb-0 text-primary">{{ $stats['sms'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">WhatsApp (7d)</div>
                        <h3 class="mb-0 text-success">{{ $stats['whatsapp'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row align-items-end">
                        @if($isSuper)
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('message_log.institution') }}</label>
                            <select name="institution_id" class="form-control default-select">
                                <option value="">{{ __('message_log.all_institutions') }}</option>
                                @foreach($institutions as $inst)
                                    <option value="{{ $inst->id }}">{{ $inst->code }} — {{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('message_log.channel') }}</label>
                            <select name="channel" class="form-control default-select">
                                <option value="">{{ __('message_log.all_channels') }}</option>
                                <option value="sms">SMS</option>
                                <option value="whatsapp">WhatsApp</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('message_log.status') }}</label>
                            <select name="status" class="form-control default-select">
                                <option value="">{{ __('message_log.all_statuses') }}</option>
                                <option value="sent">{{ __('message_log.status_sent') }}</option>
                                <option value="failed">{{ __('message_log.status_failed') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('message_log.event') }}</label>
                            <select name="event_key" class="form-control default-select">
                                <option value="">{{ __('message_log.all_events') }}</option>
                                @foreach($eventKeys as $key)
                                    <option value="{{ $key }}">{{ $key }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('message_log.date_from') }}</label>
                            <input type="text" name="date_from" class="form-control datepicker" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="col-md-2 mb-3">
                            <button type="button" id="filterBtn" class="btn btn-primary w-100">
                                <i class="fa fa-filter me-1"></i> {{ __('message_log.filter') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="messageLogsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('message_log.date') }}</th>
                                @if($isSuper)
                                <th>{{ __('message_log.institution') }}</th>
                                @endif
                                <th>{{ __('message_log.channel') }}</th>
                                <th>{{ __('message_log.event') }}</th>
                                <th>{{ __('message_log.recipient') }}</th>
                                <th>{{ __('message_log.status') }}</th>
                                <th>{{ __('message_log.provider') }}</th>
                                <th>{{ __('message_log.credited') }}</th>
                                <th>{{ __('message_log.error') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <p class="text-muted small mt-3 mb-0">{{ __('message_log.storage_note') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function () {
    const isSuper = @json($isSuper);
    const columns = [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'created_at', name: 'created_at' },
    ];
    if (isSuper) {
        columns.push({ data: 'institution_name', name: 'institution_id', orderable: false });
    }
    columns.push(
        { data: 'channel', name: 'channel' },
        { data: 'event_key', name: 'event_key' },
        { data: 'to_masked', name: 'to_masked' },
        { data: 'status', name: 'status' },
        { data: 'provider', name: 'provider' },
        { data: 'credited', name: 'credited', orderable: false },
        { data: 'error', name: 'error', orderable: false }
    );

    const table = $('#messageLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('message-logs.index')),
            data: function (d) {
                const form = $('#filterForm').serializeArray();
                form.forEach(function (item) {
                    d[item.name] = item.value;
                });
            }
        },
        columns: columns,
        order: [[1, 'desc']],
        pageLength: 25
    });

    $('#filterBtn').on('click', function () {
        table.ajax.reload();
    });
});
</script>
@endsection
