<?php

namespace App\Http\Controllers;

use App\Jobs\CreateSchoolBackupJob;
use App\Jobs\ImportSchoolBackupJob;
use App\Jobs\UploadSchoolBackupToDriveJob;
use App\Models\InstitutionSetting;
use App\Models\SchoolBackup;
use App\Models\SchoolBackupDriveAccount;
use App\Services\Backup\GoogleDriveBackupService;
use App\Services\Backup\SchoolBackupImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolBackupController extends BaseController
{
    public function __construct(
        protected GoogleDriveBackupService $drive,
        protected SchoolBackupImporter $importer
    ) {
        $this->middleware('auth');
        $this->setPageTitle(__('school_backup.page_title'));
    }

    public function index()
    {
        $this->authorizeAdminOrPermission('school_backup.view');
        $institutionId = $this->requireInstitutionId();

        $backups = SchoolBackup::query()
            ->where('institution_id', $institutionId)
            ->with('triggeredBy:id,name')
            ->latest('id')
            ->limit(50)
            ->get();

        $driveAccount = SchoolBackupDriveAccount::where('institution_id', $institutionId)->first();
        $schedule = InstitutionSetting::get($institutionId, 'backup_schedule', 'off');
        $includeFiles = InstitutionSetting::get($institutionId, 'backup_include_files', '1') !== '0';
        $importResult = Cache::get('school-backup-import-result:' . $institutionId);
        $driveConfigured = $this->drive->isConfigured();
        $running = SchoolBackup::where('institution_id', $institutionId)
            ->whereIn('status', ['pending', 'running'])
            ->exists();
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');
        $platformOauth = [
            'client_id' => $this->drive->clientId() ?? '',
            'redirect_uri' => $this->drive->redirectUri(),
            'has_secret' => filled($this->drive->clientSecret()),
        ];

        return view('school_backups.index', compact(
            'backups',
            'driveAccount',
            'schedule',
            'includeFiles',
            'importResult',
            'driveConfigured',
            'running',
            'institutionId',
            'isSuperAdmin',
            'platformOauth'
        ));
    }

    public function export(Request $request)
    {
        $this->authorizeAdminOrPermission('school_backup.create');
        $institutionId = $this->requireInstitutionId();

        $request->validate([
            'include_files' => 'nullable|boolean',
            'upload_drive' => 'nullable|boolean',
        ]);

        if (SchoolBackup::where('institution_id', $institutionId)->whereIn('status', ['pending', 'running'])->exists()) {
            return back()->with('error', __('school_backup.already_running'));
        }

        $includeFiles = $request->boolean('include_files', true);
        InstitutionSetting::set($institutionId, 'backup_include_files', $includeFiles ? '1' : '0', 'backup');

        $backup = SchoolBackup::create([
            'institution_id' => $institutionId,
            'type' => 'manual',
            'status' => 'pending',
            'include_files' => $includeFiles,
            'triggered_by' => Auth::id(),
        ]);

        $upload = $request->boolean('upload_drive')
            && SchoolBackupDriveAccount::where('institution_id', $institutionId)->exists();

        // Run sync in local/testing when queue is sync; otherwise dispatch
        CreateSchoolBackupJob::dispatch($backup->id, $upload);

        return back()->with('success', __('school_backup.export_started'));
    }

    public function download(SchoolBackup $schoolBackup)
    {
        $this->authorizeAdminOrPermission('school_backup.view');
        $institutionId = $this->requireInstitutionId();
        $this->assertOwnsBackup($schoolBackup, $institutionId);

        if (!$schoolBackup->isDownloadable()) {
            return back()->with('error', __('school_backup.not_ready'));
        }

        return response()->download(
            storage_path('app/' . $schoolBackup->disk_path),
            'school-backup-' . ($schoolBackup->uuid) . '.zip'
        );
    }

    public function destroy(SchoolBackup $schoolBackup)
    {
        $this->authorizeAdminOrPermission('school_backup.manage');
        $institutionId = $this->requireInstitutionId();
        $this->assertOwnsBackup($schoolBackup, $institutionId);

        if ($schoolBackup->disk_path) {
            Storage::disk('local')->delete($schoolBackup->disk_path);
        }
        $schoolBackup->delete();

        return back()->with('success', __('school_backup.deleted'));
    }

    public function previewImport(Request $request)
    {
        $this->authorizeAdminOrPermission('school_backup.manage');
        $this->requireInstitutionId();

        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:512000',
        ]);

        $path = $request->file('backup_file')->storeAs(
            'school-backups/uploads',
            Str::uuid() . '.zip',
            'local'
        );
        $absolute = storage_path('app/' . $path);

        try {
            $preview = $this->importer->preview($absolute);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return back()->with('error', $e->getMessage());
        }

        session([
            'school_backup_import_path' => $path,
            'school_backup_import_preview' => $preview,
        ]);

        return back()->with('info', __('school_backup.preview_ready'));
    }

    public function confirmImport(Request $request)
    {
        $this->authorizeAdminOrPermission('school_backup.manage');
        $institutionId = $this->requireInstitutionId();

        $request->validate([
            'replace' => 'nullable|boolean',
        ]);

        $path = session('school_backup_import_path');
        if (!$path || !Storage::disk('local')->exists($path)) {
            return back()->with('error', __('school_backup.preview_missing'));
        }

        $replace = $request->boolean('replace') && Auth::user()->hasRole('Super Admin');
        $absolute = storage_path('app/' . $path);

        // Move out of session-managed path so job owns cleanup
        $jobPath = storage_path('app/school-backups/uploads/job-' . Str::uuid() . '.zip');
        rename($absolute, $jobPath);
        session()->forget(['school_backup_import_path', 'school_backup_import_preview']);

        ImportSchoolBackupJob::dispatch($institutionId, $jobPath, $replace, Auth::id());

        return back()->with('success', __('school_backup.import_started'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAdminOrPermission('school_backup.manage');
        $institutionId = $this->requireInstitutionId();

        $data = $request->validate([
            'backup_schedule' => 'required|in:off,daily,weekly',
            'backup_include_files' => 'nullable|boolean',
            'folder_name' => 'nullable|string|max:120',
        ]);

        InstitutionSetting::set($institutionId, 'backup_schedule', $data['backup_schedule'], 'backup');
        InstitutionSetting::set(
            $institutionId,
            'backup_include_files',
            $request->boolean('backup_include_files', true) ? '1' : '0',
            'backup'
        );

        if (!empty($data['folder_name'])) {
            $account = SchoolBackupDriveAccount::firstOrNew(['institution_id' => $institutionId]);
            $account->folder_name = $data['folder_name'];
            $account->save();
        }

        return back()->with('success', __('school_backup.settings_saved'));
    }

    public function updatePlatformOauth(Request $request)
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            abort(403);
        }

        $data = $request->validate([
            'google_drive_client_id' => 'required|string|max:255',
            'google_drive_client_secret' => 'nullable|string|max:255',
            'google_drive_redirect_uri' => 'required|url|max:500',
        ]);

        $this->drive->savePlatformOAuth(
            $data['google_drive_client_id'],
            $data['google_drive_client_secret'] ?? null,
            $data['google_drive_redirect_uri']
        );

        return back()->with('success', __('school_backup.platform_oauth_saved'));
    }

    public function driveRedirect()
    {
        $this->authorizeAdminOrPermission('school_backup.manage');
        $institutionId = $this->requireInstitutionId();

        if (!$this->drive->isConfigured()) {
            return back()->with('error', Auth::user()->hasRole('Super Admin')
                ? __('school_backup.drive_not_configured_admin')
                : __('school_backup.drive_not_configured'));
        }

        return redirect()->away($this->drive->authorizationUrl($institutionId));
    }

    public function driveCallback(Request $request)
    {
        $this->authorizeAdminOrPermission('school_backup.manage');

        if ($request->filled('error')) {
            return redirect()->route('school-backups.index')
                ->with('error', __('school_backup.drive_denied'));
        }

        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        try {
            $this->drive->handleCallback($request->code, $request->state);
        } catch (\Throwable $e) {
            return redirect()->route('school-backups.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('school-backups.index')
            ->with('success', __('school_backup.drive_connected'));
    }

    public function driveDisconnect()
    {
        $this->authorizeAdminOrPermission('school_backup.manage');
        $institutionId = $this->requireInstitutionId();
        $this->drive->disconnect($institutionId);

        return back()->with('success', __('school_backup.drive_disconnected'));
    }

    public function uploadToDrive(SchoolBackup $schoolBackup)
    {
        $this->authorizeAdminOrPermission('school_backup.manage');
        $institutionId = $this->requireInstitutionId();
        $this->assertOwnsBackup($schoolBackup, $institutionId);

        if ($schoolBackup->status !== 'completed') {
            return back()->with('error', __('school_backup.not_ready'));
        }

        UploadSchoolBackupToDriveJob::dispatch($schoolBackup->id);

        return back()->with('success', __('school_backup.drive_upload_started'));
    }

    private function requireInstitutionId(): int
    {
        $institutionId = $this->getInstitutionId();
        if (!$institutionId || $institutionId === 'global') {
            abort(403, __('school_backup.select_institution'));
        }

        return (int) $institutionId;
    }

    private function assertOwnsBackup(SchoolBackup $backup, int $institutionId): void
    {
        if ((int) $backup->institution_id !== $institutionId) {
            abort(403);
        }
    }
}
