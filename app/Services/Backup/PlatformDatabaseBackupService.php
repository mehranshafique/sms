<?php

namespace App\Services\Backup;

use App\Models\InstitutionSetting;
use App\Models\PlatformDatabaseBackup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PlatformDatabaseBackupService
{
    public const SETTING_SCHEDULE = 'platform_db_backup_schedule';
    public const SETTING_KEEP = 'platform_db_backup_keep';

    public function create(PlatformDatabaseBackup $backup): PlatformDatabaseBackup
    {
        $backup->update([
            'status' => 'running',
            'error_message' => null,
        ]);

        try {
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            if (($config['driver'] ?? '') !== 'mysql') {
                throw new \RuntimeException('Platform database backup currently supports MySQL only.');
            }

            $dir = 'platform-database-backups';
            Storage::disk('local')->makeDirectory($dir);

            $filename = 'digitex-db-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.sql.gz';
            $relative = $dir.'/'.$filename;
            $absolute = Storage::disk('local')->path($relative);

            $this->runMysqldump($config, $absolute);

            if (! File::exists($absolute) || File::size($absolute) < 32) {
                throw new \RuntimeException('Backup file was not created or is empty. Check that mysqldump is installed and DB credentials are correct.');
            }

            $backup->update([
                'status' => 'ready',
                'disk' => 'local',
                'path' => $relative,
                'filename' => $filename,
                'size_bytes' => File::size($absolute),
                'completed_at' => now(),
                'error_message' => null,
            ]);

            $this->pruneOldBackups();

            return $backup->fresh();
        } catch (\Throwable $e) {
            Log::error('Platform DB backup failed: '.$e->getMessage());
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function runMysqldump(array $config, string $absoluteGzPath): void
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? 3306);
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = (string) ($config['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new \RuntimeException('Database connection is incomplete.');
        }

        $mysqldump = $this->resolveMysqldumpBinary();
        $tmpSql = $absoluteGzPath.'.tmp.sql';
        $cnfPath = $absoluteGzPath.'.cnf';

        $escapedPassword = str_replace(['\\', '"'], ['\\\\', '\\"'], $password);
        File::put($cnfPath, "[client]\nuser=\"{$username}\"\npassword=\"{$escapedPassword}\"\nhost=\"{$host}\"\nport=\"{$port}\"\n");
        @chmod($cnfPath, 0600);

        $command = [
            $mysqldump,
            '--defaults-extra-file='.$cnfPath,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--databases',
            $database,
            '--result-file='.$tmpSql,
        ];

        try {
            $process = new Process($command);
            $process->setTimeout(60 * 30);
            $process->run();

            if (! $process->isSuccessful()) {
                $err = trim($process->getErrorOutput() ?: $process->getOutput());
                throw new \RuntimeException($err !== '' ? $err : 'mysqldump failed.');
            }

            $this->gzipFile($tmpSql, $absoluteGzPath);
        } finally {
            @File::delete($cnfPath);
            @File::delete($tmpSql);
        }
    }

    private function gzipFile(string $sourceSql, string $destGz): void
    {
        $in = fopen($sourceSql, 'rb');
        if ($in === false) {
            throw new \RuntimeException('Unable to read temporary SQL dump.');
        }

        $out = gzopen($destGz, 'wb6');
        if ($out === false) {
            fclose($in);
            throw new \RuntimeException('Unable to create compressed backup file.');
        }

        while (! feof($in)) {
            $chunk = fread($in, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
    }

    private function resolveMysqldumpBinary(): string
    {
        $configured = config('database.backup.mysqldump_path');
        if (is_string($configured) && $configured !== '' && File::exists($configured)) {
            return $configured;
        }

        $candidates = [
            'mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
        ];

        foreach ($candidates as $bin) {
            if ($bin === 'mysqldump') {
                $which = Process::fromShellCommandline(PHP_OS_FAMILY === 'Windows' ? 'where mysqldump' : 'command -v mysqldump');
                $which->run();
                if ($which->isSuccessful()) {
                    $path = trim(explode("\n", str_replace("\r", '', $which->getOutput()))[0] ?? '');
                    if ($path !== '') {
                        return $path;
                    }
                }
                continue;
            }
            if (File::exists($bin)) {
                return $bin;
            }
        }

        throw new \RuntimeException('mysqldump binary not found. Install MySQL client tools or set DB_MYSQLDUMP_PATH in .env.');
    }

    public function pruneOldBackups(): void
    {
        $keep = max(1, (int) InstitutionSetting::get(null, self::SETTING_KEEP, 14));

        $old = PlatformDatabaseBackup::query()
            ->where('status', 'ready')
            ->orderByDesc('id')
            ->skip($keep)
            ->take(100)
            ->get();

        foreach ($old as $backup) {
            if ($backup->path) {
                Storage::disk($backup->disk)->delete($backup->path);
            }
            $backup->delete();
        }
    }

    public function schedule(): string
    {
        return (string) InstitutionSetting::get(null, self::SETTING_SCHEDULE, 'off');
    }

    public function setSchedule(string $schedule, int $keep = 14): void
    {
        if (! in_array($schedule, ['off', 'daily', 'weekly'], true)) {
            $schedule = 'off';
        }
        InstitutionSetting::set(null, self::SETTING_SCHEDULE, $schedule, 'backup');
        InstitutionSetting::set(null, self::SETTING_KEEP, (string) max(1, min(90, $keep)), 'backup');
    }

    public function shouldRunScheduledNow(): bool
    {
        $schedule = $this->schedule();
        if ($schedule === 'off') {
            return false;
        }

        $last = PlatformDatabaseBackup::query()
            ->where('type', 'scheduled')
            ->where('status', 'ready')
            ->latest('id')
            ->first();

        if (! $last || ! $last->completed_at) {
            return true;
        }

        if ($schedule === 'daily') {
            return $last->completed_at->lt(now()->subDay());
        }

        if ($schedule === 'weekly') {
            return $last->completed_at->lt(now()->subWeek());
        }

        return false;
    }
}
