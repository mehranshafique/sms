<?php

namespace App\Services\Backup;

use App\Models\Institution;
use App\Models\SchoolBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

class SchoolBackupExporter
{
    public const SCHEMA_VERSION = 1;

    /**
     * Tables keyed by name => institution column (null = special handling).
     *
     * @var array<string, string|null>
     */
    public const TABLES = [
        'institution_settings' => 'institution_id',
        'campuses' => 'institution_id',
        'academic_sessions' => 'institution_id',
        'departments' => 'institution_id',
        'grade_levels' => 'institution_id',
        'streams' => 'institution_id',
        'class_sections' => 'institution_id',
        'subjects' => 'institution_id',
        'class_subjects' => 'institution_id',
        'programs' => 'institution_id',
        'academic_units' => 'institution_id',
        'roles' => 'institution_id',
        'staff' => 'institution_id',
        'students' => 'institution_id',
        'parents' => 'institution_id',
        'student_enrollments' => 'institution_id',
        'enrollments' => 'institution_id',
        'exams' => 'institution_id',
        'exam_schedules' => 'institution_id',
        'exam_records' => 'institution_id',
        'fee_types' => 'institution_id',
        'fee_structures' => 'institution_id',
        'invoices' => 'institution_id',
        'payments' => 'institution_id',
        'student_attendances' => 'institution_id',
        'staff_attendances' => 'institution_id',
        'notices' => 'institution_id',
        'timetables' => 'institution_id',
        'assignments' => 'institution_id',
        'student_requests' => 'institution_id',
        'disciplinary_records' => 'institution_id',
        'sms_templates' => 'institution_id',
        'email_templates' => 'institution_id',
    ];

    /** @var list<string> */
    public const FILE_COLUMNS = [
        'institutions.logo',
        'users.profile_picture',
        'students.student_photo',
        'staff.photo',
        'payment_proofs.file_path',
        'payment_proofs.proof_path',
    ];

    public function export(SchoolBackup $backup): SchoolBackup
    {
        $institution = Institution::findOrFail($backup->institution_id);
        $backup->update(['status' => 'running', 'error_message' => null]);

        $workDir = storage_path('app/school-backups/tmp/' . $backup->uuid);
        if (!is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }
        if (!is_dir($workDir . '/data')) {
            mkdir($workDir . '/data', 0755, true);
        }
        if (!is_dir($workDir . '/files')) {
            mkdir($workDir . '/files', 0755, true);
        }

        try {
            $summary = [];
            $filePaths = [];

            // Institution row (single)
            $instRow = DB::table('institutions')->where('id', $institution->id)->first();
            $this->writeJsonl($workDir . '/data/institutions.jsonl', [$instRow]);
            $summary['institutions'] = 1;
            $filePaths = array_merge($filePaths, $this->collectFilePathsFromRow('institutions', (array) $instRow));

            foreach (self::TABLES as $table => $column) {
                if (!Schema::hasTable($table) || !$column || !Schema::hasColumn($table, $column)) {
                    continue;
                }

                $rows = DB::table($table)->where($column, $institution->id)->orderBy('id')->get();
                $summary[$table] = $rows->count();
                $this->writeJsonl($workDir . '/data/' . $table . '.jsonl', $rows->all());

                foreach ($rows as $row) {
                    $filePaths = array_merge($filePaths, $this->collectFilePathsFromRow($table, (array) $row));
                }
            }

            // Users linked to this institution
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'institute_id')) {
                $users = DB::table('users')->where('institute_id', $institution->id)->orderBy('id')->get();
                $summary['users'] = $users->count();
                $this->writeJsonl($workDir . '/data/users.jsonl', $users->all());
                foreach ($users as $row) {
                    $filePaths = array_merge($filePaths, $this->collectFilePathsFromRow('users', (array) $row));
                }

                $userIds = $users->pluck('id')->all();
                $roleIds = DB::table('roles')->where('institution_id', $institution->id)->pluck('id')->all();

                if (Schema::hasTable('model_has_roles') && $userIds) {
                    $mhr = DB::table('model_has_roles')
                        ->where('model_type', \App\Models\User::class)
                        ->whereIn('model_id', $userIds)
                        ->when($roleIds, fn ($q) => $q->whereIn('role_id', $roleIds))
                        ->get();
                    if ($roleIds === []) {
                        $mhr = DB::table('model_has_roles')
                            ->where('model_type', \App\Models\User::class)
                            ->whereIn('model_id', $userIds)
                            ->get();
                    }
                    $summary['model_has_roles'] = $mhr->count();
                    $this->writeJsonl($workDir . '/data/model_has_roles.jsonl', $mhr->all());
                }

                if (Schema::hasTable('role_has_permissions') && $roleIds) {
                    $rhp = DB::table('role_has_permissions')->whereIn('role_id', $roleIds)->get();
                    $summary['role_has_permissions'] = $rhp->count();
                    $this->writeJsonl($workDir . '/data/role_has_permissions.jsonl', $rhp->all());
                }
            }

            if ($backup->include_files) {
                $copied = 0;
                foreach (array_unique(array_filter($filePaths)) as $relative) {
                    $relative = ltrim(str_replace('\\', '/', $relative), '/');
                    if (Str::startsWith($relative, 'storage/')) {
                        $relative = substr($relative, strlen('storage/'));
                    }
                    $candidates = [
                        storage_path('app/public/' . $relative),
                        storage_path('app/' . $relative),
                        public_path('storage/' . $relative),
                    ];
                    foreach ($candidates as $abs) {
                        if (is_file($abs)) {
                            $dest = $workDir . '/files/' . $relative;
                            $dir = dirname($dest);
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }
                            copy($abs, $dest);
                            $copied++;
                            break;
                        }
                    }
                }
                $summary['files_copied'] = $copied;
            }

            $manifest = [
                'format' => 'digitex-school-backup',
                'schema_version' => self::SCHEMA_VERSION,
                'app_version' => config('app.version', '1.0'),
                'exported_at' => now()->toIso8601String(),
                'institution' => [
                    'id' => $institution->id,
                    'code' => $institution->code,
                    'name' => $institution->name,
                    'type' => $institution->type instanceof \BackedEnum ? $institution->type->value : $institution->type,
                ],
                'include_files' => (bool) $backup->include_files,
                'tables' => $summary,
            ];
            file_put_contents($workDir . '/digitex-backup.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $relativeZip = 'school-backups/' . $institution->id . '/' . $backup->uuid . '.zip';
            $absoluteZip = SchoolBackupPath::absoluteForWrite($relativeZip);

            $zip = new ZipArchive();
            if ($zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create backup ZIP.');
            }
            $this->addDirectoryToZip($zip, $workDir, '');
            $zip->close();
            unset($zip);

            if (!is_file($absoluteZip) || filesize($absoluteZip) < 22) {
                throw new \RuntimeException('Unable to create backup ZIP.');
            }

            $verify = new ZipArchive();
            $verifyFlags = defined('ZipArchive::RDONLY') ? ZipArchive::RDONLY : 0;
            if ($verify->open($absoluteZip, $verifyFlags) !== true) {
                throw new \RuntimeException('Unable to create backup ZIP.');
            }
            $verify->close();
            unset($verify);

            $backup->update([
                'status' => 'completed',
                'disk_path' => $relativeZip,
                'file_size' => filesize($absoluteZip) ?: 0,
                'checksum' => hash_file('sha256', $absoluteZip),
                'summary' => $summary,
            ]);

            $this->pruneOldBackups((int) $institution->id);

            return $backup->fresh();
        } catch (\Throwable $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            $this->deleteDirectory($workDir);
        }
    }

    private function writeJsonl(string $path, array $rows): void
    {
        $fh = fopen($path, 'w');
        foreach ($rows as $row) {
            $data = (array) $row;
            fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE) . "\n");
        }
        fclose($fh);
    }

    /** @return list<string> */
    private function collectFilePathsFromRow(string $table, array $row): array
    {
        $paths = [];
        foreach (self::FILE_COLUMNS as $spec) {
            [$t, $col] = explode('.', $spec, 2);
            if ($t === $table && !empty($row[$col]) && is_string($row[$col])) {
                $paths[] = $row[$col];
            }
        }

        return $paths;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $prefix): void
    {
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            $local = ltrim($prefix . '/' . $item, '/');
            if (is_dir($path)) {
                $zip->addEmptyDir($local);
                $this->addDirectoryToZip($zip, $path, $local);
            } else {
                // addFromString copies bytes immediately so the ZIP stays valid after
                // the work directory is deleted (ZipArchive::addFile is deferred).
                $contents = file_get_contents($path);
                if ($contents === false) {
                    throw new \RuntimeException('Unable to read file for backup ZIP: ' . $local);
                }
                $zip->addFromString(str_replace('\\', '/', $local), $contents);
            }
        }
    }

    private function pruneOldBackups(int $institutionId): void
    {
        $keep = (int) (\App\Models\InstitutionSetting::get($institutionId, 'backup_retain_count', 10) ?: 10);
        $keep = max(1, $keep);

        $old = SchoolBackup::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->skip($keep)
            ->take(100)
            ->get();

        foreach ($old as $backup) {
            if ($backup->disk_path) {
                SchoolBackupPath::delete($backup->disk_path);
            }
            $backup->delete();
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
