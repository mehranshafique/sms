@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm">
            <div class="col-sm-8 p-0">
                <h4 class="mb-1">{{ __('platform_backup.page_title') }}</h4>
                <p class="mb-0 text-muted">{{ __('platform_backup.subtitle') }}</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">{{ __('platform_backup.export_now') }}</h5></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('platform_backup.export_help') }}</p>
                        <form method="POST" action="{{ route('platform.database-backups.store') }}">
                            @csrf
                            <button class="btn btn-primary" @disabled($running)>
                                <i class="la la-database me-1"></i> {{ __('platform_backup.export_now') }}
                            </button>
                        </form>
                        @if($running)
                            <p class="text-warning small mt-3 mb-0">{{ __('platform_backup.already_running') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">{{ __('platform_backup.settings') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('platform.database-backups.settings') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('platform_backup.schedule') }}</label>
                                <select name="backup_schedule" class="form-control">
                                    <option value="off" @selected($schedule === 'off')>{{ __('platform_backup.schedule_off') }}</option>
                                    <option value="daily" @selected($schedule === 'daily')>{{ __('platform_backup.schedule_daily') }}</option>
                                    <option value="weekly" @selected($schedule === 'weekly')>{{ __('platform_backup.schedule_weekly') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('platform_backup.keep') }}</label>
                                <input type="number" name="backup_keep" class="form-control" min="1" max="90" value="{{ $keep }}">
                                <div class="form-text">{{ __('platform_backup.keep_help') }}</div>
                            </div>
                            <button class="btn btn-primary btn-sm">{{ __('platform_backup.save_settings') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('platform_backup.history') }}</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="platformBackupTable">
                        <thead>
                            <tr>
                                <th>{{ __('platform_backup.created') }}</th>
                                <th>{{ __('platform_backup.type') }}</th>
                                <th>{{ __('platform_backup.status') }}</th>
                                <th>{{ __('platform_backup.size') }}</th>
                                <th>{{ __('platform_backup.triggered_by') }}</th>
                                <th class="text-end">{{ __('platform_backup.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $backup)
                                <tr>
                                    <td>{{ $backup->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ __('platform_backup.type_'.$backup->type) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $backup->status === 'ready' ? 'success' : ($backup->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ __('platform_backup.status_'.$backup->status) }}
                                        </span>
                                        @if($backup->error_message)
                                            <div class="small text-danger">{{ \Illuminate\Support\Str::limit($backup->error_message, 100) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $backup->status === 'ready' ? $backup->sizeLabel() : '—' }}</td>
                                    <td>{{ $backup->triggeredByUser?->name ?? '—' }}</td>
                                    <td class="text-end">
                                        @if($backup->isDownloadable())
                                            <a class="btn btn-sm btn-primary" href="{{ route('platform.database-backups.download', $backup) }}">{{ __('platform_backup.download') }}</a>
                                        @endif
                                        <form method="POST" action="{{ route('platform.database-backups.destroy', $backup) }}" class="d-inline" onsubmit="return confirm(@json(__('platform_backup.confirm_delete')))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">{{ __('platform_backup.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">{{ __('platform_backup.empty') }}</td></tr>
                            @endforelse
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
        if (window.jQuery && $('#platformBackupTable tbody tr').length && !$('#platformBackupTable tbody td[colspan]').length) {
            $('#platformBackupTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                columnDefs: [{ orderable: false, targets: -1 }]
            });
        }
    });
</script>
@endsection
