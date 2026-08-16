<?php

namespace App\Services\Backup;

use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

class SchoolBackupImporter
{
    public const SUPPORTED_SCHEMA_VERSIONS = [1];

    /**
     * Import order (dependencies first). Keys are jsonl basenames without extension.
     *
     * @var list<string>
     */
    private const IMPORT_ORDER = [
        'campuses',
        'academic_sessions',
        'departments',
        'grade_levels',
        'streams',
        'class_sections',
        'subjects',
        'programs',
        'academic_units',
        'class_subjects',
        'roles',
        'users',
        'staff',
        'parents',
        'students',
        'student_enrollments',
        'enrollments',
        'exams',
        'exam_schedules',
        'exam_records',
        'fee_types',
        'fee_structures',
        'invoices',
        'payments',
        'student_attendances',
        'staff_attendances',
        'notices',
        'timetables',
        'assignments',
        'student_requests',
        'disciplinary_records',
        'sms_templates',
        'email_templates',
        'institution_settings',
        'role_has_permissions',
        'model_has_roles',
    ];

    /**
     * @return array{manifest: array, counts: array<string,int>}
     */
    public function preview(string $zipAbsolutePath): array
    {
        $manifest = $this->readManifest($zipAbsolutePath);
        $this->assertCompatible($manifest);

        return [
            'manifest' => $manifest,
            'counts' => $manifest['tables'] ?? [],
        ];
    }

    /**
     * @return array{imported: array<string,int>, warnings: list<string>}
     */
    public function import(string $zipAbsolutePath, int $targetInstitutionId, bool $replace = false): array
    {
        $manifest = $this->readManifest($zipAbsolutePath);
        $this->assertCompatible($manifest);

        $workDir = storage_path('app/school-backups/tmp/import-' . Str::uuid());
        mkdir($workDir, 0755, true);

        try {
            $zip = $this->openZip($zipAbsolutePath);
            $zip->extractTo($workDir);
            $zip->close();

            $idMaps = []; // table => [oldId => newId]
            $imported = [];
            $warnings = [];

            DB::transaction(function () use ($workDir, $targetInstitutionId, $replace, &$idMaps, &$imported, &$warnings) {
                if ($replace) {
                    // Soft safety: only clear institution_settings and non-destructive merge otherwise.
                    // Full wipe is Super-Admin-only and limited to settings + optional tables.
                    DB::table('institution_settings')->where('institution_id', $targetInstitutionId)->delete();
                }

                foreach (self::IMPORT_ORDER as $table) {
                    $path = $workDir . '/data/' . $table . '.jsonl';
                    if (!is_file($path) || !Schema::hasTable($table)) {
                        continue;
                    }

                    $rows = $this->readJsonl($path);
                    if ($rows === []) {
                        $imported[$table] = 0;
                        continue;
                    }

                    if ($table === 'role_has_permissions') {
                        $imported[$table] = $this->importRolePermissions($rows, $idMaps);
                        continue;
                    }
                    if ($table === 'model_has_roles') {
                        $imported[$table] = $this->importModelHasRoles($rows, $idMaps);
                        continue;
                    }
                    if ($table === 'institution_settings') {
                        $imported[$table] = $this->importSettings($rows, $targetInstitutionId);
                        continue;
                    }

                    $count = 0;
                    $idMaps[$table] = $idMaps[$table] ?? [];

                    foreach ($rows as $row) {
                        $oldId = $row['id'] ?? null;
                        unset($row['id']);

                        if (array_key_exists('institution_id', $row)) {
                            $row['institution_id'] = $targetInstitutionId;
                        }
                        if (array_key_exists('institute_id', $row)) {
                            $row['institute_id'] = $targetInstitutionId;
                        }

                        $row = $this->remapForeignKeys($table, $row, $idMaps);

                        // Skip columns that do not exist on this schema version
                        $row = array_filter(
                            $row,
                            fn ($_, $col) => Schema::hasColumn($table, $col),
                            ARRAY_FILTER_USE_BOTH
                        );

                        try {
                            $newId = $this->upsertRow($table, $row, $targetInstitutionId);
                            if ($oldId !== null && $newId) {
                                $idMaps[$table][(int) $oldId] = (int) $newId;
                            }
                            $count++;
                        } catch (\Throwable $e) {
                            $warnings[] = "{$table}: " . $e->getMessage();
                        }
                    }

                    $imported[$table] = $count;
                }
            });

            // Copy files
            $filesDir = $workDir . '/files';
            if (is_dir($filesDir)) {
                $this->copyFilesRecursive($filesDir, storage_path('app/public'));
            }

            return compact('imported', 'warnings');
        } finally {
            $this->deleteDirectory($workDir);
        }
    }

    private function assertCompatible(array $manifest): void
    {
        if (($manifest['format'] ?? '') !== 'digitex-school-backup') {
            throw new \InvalidArgumentException(__('school_backup.invalid_format'));
        }
        $version = (int) ($manifest['schema_version'] ?? 0);
        if (!in_array($version, self::SUPPORTED_SCHEMA_VERSIONS, true)) {
            throw new \InvalidArgumentException(__('school_backup.unsupported_version', ['version' => $version]));
        }
    }

    private function readManifest(string $zipAbsolutePath): array
    {
        $zip = $this->openZip($zipAbsolutePath);
        $json = $zip->getFromName('digitex-backup.json');
        $zip->close();
        if ($json === false) {
            throw new \InvalidArgumentException(__('school_backup.missing_manifest'));
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException(__('school_backup.invalid_manifest'));
        }

        return $data;
    }

    private function openZip(string $zipAbsolutePath): ZipArchive
    {
        if (!is_file($zipAbsolutePath) || filesize($zipAbsolutePath) < 22) {
            throw new \RuntimeException(__('school_backup.unable_open_zip'));
        }

        $zip = new ZipArchive();
        $flags = defined('ZipArchive::RDONLY') ? ZipArchive::RDONLY : 0;
        $status = $zip->open($zipAbsolutePath, $flags);
        if ($status !== true) {
            Log::warning('School backup ZIP open failed', [
                'path' => $zipAbsolutePath,
                'size' => filesize($zipAbsolutePath),
                'status' => $status,
            ]);
            throw new \RuntimeException(__('school_backup.unable_open_zip'));
        }

        return $zip;
    }

    /** @return list<array<string,mixed>> */
    private function readJsonl(string $path): array
    {
        $rows = [];
        $fh = fopen($path, 'r');
        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }
        fclose($fh);

        return $rows;
    }

    private function remapForeignKeys(string $table, array $row, array $idMaps): array
    {
        $map = [
            'campus_id' => 'campuses',
            'academic_session_id' => 'academic_sessions',
            'department_id' => 'departments',
            'grade_level_id' => 'grade_levels',
            'stream_id' => 'streams',
            'class_section_id' => 'class_sections',
            'subject_id' => 'subjects',
            'program_id' => 'programs',
            'student_id' => 'students',
            'staff_id' => 'staff',
            'parent_id' => 'parents',
            'exam_id' => 'exams',
            'fee_type_id' => 'fee_types',
            'fee_structure_id' => 'fee_structures',
            'invoice_id' => 'invoices',
            'user_id' => 'users',
            'role_id' => 'roles',
        ];

        foreach ($map as $column => $sourceTable) {
            if (!empty($row[$column]) && isset($idMaps[$sourceTable][(int) $row[$column]])) {
                $row[$column] = $idMaps[$sourceTable][(int) $row[$column]];
            }
        }

        return $row;
    }

    private function upsertRow(string $table, array $row, int $institutionId): ?int
    {
        // Natural key strategies
        if ($table === 'users' && !empty($row['email'])) {
            $existing = DB::table('users')->where('email', $row['email'])->first();
            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update(collect($row)->except(['password'])->all() + ['institute_id' => $institutionId]);
                return (int) $existing->id;
            }
        }

        if ($table === 'students' && !empty($row['admission_number'])) {
            $existing = DB::table('students')
                ->where('institution_id', $institutionId)
                ->where('admission_number', $row['admission_number'])
                ->first();
            if ($existing) {
                DB::table('students')->where('id', $existing->id)->update($row);
                return (int) $existing->id;
            }

            // admission_number is globally unique — remapping for cross-school import
            if (DB::table('students')->where('admission_number', $row['admission_number'])->exists()) {
                $row['admission_number'] = $row['admission_number'] . '-I' . $institutionId;
            }
        }

        if ($table === 'roles' && !empty($row['name'])) {
            $existing = DB::table('roles')
                ->where('institution_id', $institutionId)
                ->where('name', $row['name'])
                ->where('guard_name', $row['guard_name'] ?? 'web')
                ->first();
            if ($existing) {
                return (int) $existing->id;
            }
        }

        if (in_array($table, ['campuses', 'academic_sessions', 'departments', 'grade_levels', 'subjects', 'fee_types'], true)
            && !empty($row['name'])) {
            $q = DB::table($table)->where('institution_id', $institutionId)->where('name', $row['name']);
            if (Schema::hasColumn($table, 'code') && !empty($row['code'])) {
                $q->where('code', $row['code']);
            }
            $existing = $q->first();
            if ($existing) {
                DB::table($table)->where('id', $existing->id)->update($row);
                return (int) $existing->id;
            }
        }

        return (int) DB::table($table)->insertGetId($row);
    }

    private function importSettings(array $rows, int $institutionId): int
    {
        $count = 0;
        foreach ($rows as $row) {
            unset($row['id']);
            $row['institution_id'] = $institutionId;
            if (empty($row['key'])) {
                continue;
            }
            DB::table('institution_settings')->updateOrInsert(
                ['institution_id' => $institutionId, 'key' => $row['key']],
                [
                    'value' => $row['value'] ?? null,
                    'group' => $row['group'] ?? 'general',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function importRolePermissions(array $rows, array $idMaps): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $oldRole = (int) ($row['role_id'] ?? 0);
            $permId = (int) ($row['permission_id'] ?? 0);
            $newRole = $idMaps['roles'][$oldRole] ?? null;
            if (!$newRole || !$permId) {
                continue;
            }
            if (!DB::table('permissions')->where('id', $permId)->exists()) {
                continue;
            }
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $newRole,
                'permission_id' => $permId,
            ]);
            $count++;
        }

        return $count;
    }

    private function importModelHasRoles(array $rows, array $idMaps): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $oldUser = (int) ($row['model_id'] ?? 0);
            $oldRole = (int) ($row['role_id'] ?? 0);
            $newUser = $idMaps['users'][$oldUser] ?? null;
            $newRole = $idMaps['roles'][$oldRole] ?? null;
            if (!$newUser || !$newRole) {
                continue;
            }
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $newRole,
                'model_type' => \App\Models\User::class,
                'model_id' => $newUser,
            ]);
            $count++;
        }

        return $count;
    }

    private function copyFilesRecursive(string $from, string $to): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $target = $to . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $dir = dirname($target);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($item->getPathname(), $target);
            }
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
