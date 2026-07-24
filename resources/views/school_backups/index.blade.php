@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm">
            <div class="col-sm-8 p-0">
                <h4 class="mb-1">{{ __('school_backup.page_title') }}</h4>
                <p class="mb-0 text-muted">{{ __('school_backup.subtitle') }}</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">{{ __('school_backup.export_now') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('school-backups.export') }}">
                            @csrf
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="include_files" value="1" id="includeFiles" @checked($includeFiles)>
                                <label class="form-check-label" for="includeFiles">{{ __('school_backup.include_files') }}</label>
                            </div>
                            @if($driveAccount?->isConnected())
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="upload_drive" value="1" id="uploadDrive">
                                <label class="form-check-label" for="uploadDrive">{{ __('school_backup.also_upload_drive') }}</label>
                            </div>
                            @endif
                            <button class="btn btn-primary" @disabled($running)>
                                <i class="la la-download me-1"></i> {{ __('school_backup.export_now') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">{{ __('school_backup.import') }}</h5></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('school_backup.import_help') }}</p>
                        <form method="POST" action="{{ route('school-backups.import.preview') }}" enctype="multipart/form-data" class="mb-3">
                            @csrf
                            <input type="file" name="backup_file" accept=".zip,application/zip" class="form-control mb-2" required>
                            <button class="btn btn-outline-primary btn-sm">{{ __('school_backup.preview') }}</button>
                        </form>

                        @php $preview = session('school_backup_import_preview'); @endphp
                        @if($preview)
                            <div class="border rounded p-3 bg-light">
                                <div class="mb-2">
                                    <strong>{{ __('school_backup.source_school') }}:</strong>
                                    {{ $preview['manifest']['institution']['name'] ?? '—' }}
                                    ({{ $preview['manifest']['institution']['code'] ?? '—' }})
                                </div>
                                <div class="small mb-2"><strong>{{ __('school_backup.table_counts') }}</strong></div>
                                <ul class="small mb-3" style="max-height:140px;overflow:auto;">
                                    @foreach(($preview['counts'] ?? []) as $table => $count)
                                        <li>{{ $table }}: {{ $count }}</li>
                                    @endforeach
                                </ul>
                                <form method="POST" action="{{ route('school-backups.import.confirm') }}">
                                    @csrf
                                    @if(auth()->user()->hasRole('Super Admin'))
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="replace" value="1" id="replaceSettings">
                                        <label class="form-check-label" for="replaceSettings">{{ __('school_backup.replace_warning') }}</label>
                                    </div>
                                    @endif
                                    <button class="btn btn-success btn-sm">{{ __('school_backup.confirm_import') }}</button>
                                </form>
                            </div>
                        @endif

                        @if($importResult)
                            <div class="alert {{ ($importResult['ok'] ?? false) ? 'alert-success' : 'alert-danger' }} mt-3 mb-0">
                                <strong>{{ __('school_backup.last_import') }}</strong>
                                @if($importResult['ok'] ?? false)
                                    <div class="small mt-1">{{ json_encode($importResult['imported'] ?? []) }}</div>
                                    @if(!empty($importResult['warnings']))
                                        <div class="small mt-1">{{ __('school_backup.warnings') }}: {{ implode('; ', $importResult['warnings']) }}</div>
                                    @endif
                                @else
                                    <div class="small mt-1">{{ $importResult['error'] ?? 'Error' }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">{{ __('school_backup.settings') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('school-backups.settings') }}" class="mb-3">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('school_backup.schedule') }}</label>
                                <select name="backup_schedule" class="form-control">
                                    <option value="off" @selected($schedule === 'off')>{{ __('school_backup.schedule_off') }}</option>
                                    <option value="daily" @selected($schedule === 'daily')>{{ __('school_backup.schedule_daily') }}</option>
                                    <option value="weekly" @selected($schedule === 'weekly')>{{ __('school_backup.schedule_weekly') }}</option>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="backup_include_files" value="1" id="schedFiles" @checked($includeFiles)>
                                <label class="form-check-label" for="schedFiles">{{ __('school_backup.include_files') }}</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('school_backup.drive_folder') }}</label>
                                <input type="text" name="folder_name" class="form-control" value="{{ $driveAccount->folder_name ?? 'Digitex School Backups' }}">
                            </div>
                            <button class="btn btn-primary btn-sm">{{ __('school_backup.save_settings') }}</button>
                        </form>

                        <hr>
                        <h6 class="mb-2">{{ __('school_backup.drive_section') }}</h6>
                        <p class="text-muted small">{{ __('school_backup.drive_school_help') }}</p>
                        @if($driveConfigured)
                            @if($driveAccount?->isConnected())
                                <p class="mb-2">{{ __('school_backup.connected_as', ['email' => $driveAccount->google_email ?? '—']) }}</p>
                                <form method="POST" action="{{ route('school-backups.drive.disconnect') }}">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm">{{ __('school_backup.drive_disconnect') }}</button>
                                </form>
                            @else
                                <p class="text-muted">{{ __('school_backup.not_connected') }}</p>
                                <a href="{{ route('school-backups.drive.redirect') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="la la-google me-1"></i> {{ __('school_backup.drive_connect') }}
                                </a>
                            @endif
                        @else
                            <p class="text-muted small mb-0">
                                {{ !empty($isSuperAdmin) ? __('school_backup.drive_not_configured_admin') : __('school_backup.drive_not_configured') }}
                            </p>
                        @endif

                        @if(!empty($isSuperAdmin))
                            <hr>
                            <h6 class="mb-2">{{ __('school_backup.platform_oauth_title') }}</h6>
                            <p class="text-muted small">{{ __('school_backup.platform_oauth_help') }}</p>
                            <form method="POST" action="{{ route('school-backups.platform-oauth') }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">{{ __('school_backup.oauth_client_id') }}</label>
                                    <input type="text" name="google_drive_client_id" class="form-control form-control-sm" value="{{ $platformOauth['client_id'] ?? '' }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">{{ __('school_backup.oauth_client_secret') }}</label>
                                    <input type="password" name="google_drive_client_secret" class="form-control form-control-sm" placeholder="{{ ($platformOauth['has_secret'] ?? false) ? __('school_backup.oauth_secret_kept') : '' }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">{{ __('school_backup.oauth_redirect') }}</label>
                                    <input type="url" name="google_drive_redirect_uri" class="form-control form-control-sm" value="{{ $platformOauth['redirect_uri'] ?? url('/school-backups/drive/callback') }}" required>
                                </div>
                                <button class="btn btn-dark btn-sm">{{ __('school_backup.save_platform_oauth') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('school_backup.history') }}</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="schoolBackupTable">
                        <thead>
                            <tr>
                                <th>{{ __('school_backup.created') }}</th>
                                <th>{{ __('school_backup.type') }}</th>
                                <th>{{ __('school_backup.status') }}</th>
                                <th>{{ __('school_backup.size') }}</th>
                                <th class="text-end">{{ __('school_backup.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $backup)
                                <tr>
                                    <td>{{ $backup->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ __('school_backup.' . $backup->type) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $backup->status === 'completed' ? 'success' : ($backup->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ __('school_backup.' . $backup->status) }}
                                        </span>
                                        @if($backup->error_message)
                                            <div class="small text-danger">{{ \Illuminate\Support\Str::limit($backup->error_message, 80) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $backup->file_size ? number_format($backup->file_size / 1024, 1) . ' KB' : '—' }}</td>
                                    <td class="text-end">
                                        @if($backup->status === 'completed')
                                            <a class="btn btn-sm btn-primary" href="{{ route('school-backups.download', $backup) }}">{{ __('school_backup.download') }}</a>
                                            @if($driveAccount?->isConnected())
                                                <form method="POST" action="{{ route('school-backups.drive.upload', $backup) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-secondary">{{ __('school_backup.upload_drive') }}</button>
                                                </form>
                                            @endif
                                        @endif
                                        <form method="POST" action="{{ route('school-backups.destroy', $backup) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">{{ __('school_backup.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">{{ __('school_backup.history') }} — —</td></tr>
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
        if (window.jQuery && $('#schoolBackupTable tbody tr').length && !$('#schoolBackupTable tbody td[colspan]').length) {
            $('#schoolBackupTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                columnDefs: [{ orderable: false, targets: -1 }]
            });
        }
    });
</script>
@endsection
