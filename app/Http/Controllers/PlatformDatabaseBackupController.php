<?php

namespace App\Http\Controllers;

use App\Jobs\CreatePlatformDatabaseBackupJob;
use App\Models\PlatformDatabaseBackup;
use App\Services\Backup\PlatformDatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformDatabaseBackupController extends BaseController
{
    public function __construct(protected PlatformDatabaseBackupService $service)
    {
        parent::__construct();
        $this->middleware('auth');
    }

    public function index()
    {
        $this->ensureSuperAdmin();
        $this->setPageTitle(__('platform_backup.page_title'));

        $backups = PlatformDatabaseBackup::query()
            ->with('triggeredByUser:id,name')
            ->latest('id')
            ->limit(50)
            ->get();

        $schedule = $this->service->schedule();
        $keep = (int) \App\Models\InstitutionSetting::get(null, PlatformDatabaseBackupService::SETTING_KEEP, 14);
        $running = PlatformDatabaseBackup::whereIn('status', ['pending', 'running'])->exists();

        return view('platform_backups.index', compact('backups', 'schedule', 'keep', 'running'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        if (PlatformDatabaseBackup::whereIn('status', ['pending', 'running'])->exists()) {
            return back()->with('error', __('platform_backup.already_running'));
        }

        $backup = PlatformDatabaseBackup::create([
            'type' => 'manual',
            'status' => 'pending',
            'triggered_by' => Auth::id(),
        ]);

        CreatePlatformDatabaseBackupJob::dispatch($backup->id);

        return back()->with('success', __('platform_backup.export_started'));
    }

    public function updateSettings(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'backup_schedule' => 'required|in:off,daily,weekly',
            'backup_keep' => 'required|integer|min:1|max:90',
        ]);

        $this->service->setSchedule($data['backup_schedule'], (int) $data['backup_keep']);

        return back()->with('success', __('platform_backup.settings_saved'));
    }

    public function download(PlatformDatabaseBackup $platformBackup): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $this->ensureSuperAdmin();

        if (! $platformBackup->isDownloadable()) {
            return back()->with('error', __('platform_backup.not_ready'));
        }

        return Storage::disk($platformBackup->disk)->download(
            $platformBackup->path,
            $platformBackup->filename ?: basename($platformBackup->path)
        );
    }

    public function destroy(PlatformDatabaseBackup $platformBackup)
    {
        $this->ensureSuperAdmin();

        if ($platformBackup->path) {
            Storage::disk($platformBackup->disk)->delete($platformBackup->path);
        }
        $platformBackup->delete();

        return back()->with('success', __('platform_backup.deleted'));
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->hasRole('Super Admin'), 403);
    }
}
