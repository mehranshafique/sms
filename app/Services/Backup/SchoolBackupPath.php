<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Storage;

/**
 * Resolve school-backup files on Laravel 11+'s local disk (storage/app/private)
 * while still finding older copies under storage/app.
 */
class SchoolBackupPath
{
    public static function absolute(string $relative): ?string
    {
        $relative = self::normalize($relative);
        if ($relative === '') {
            return null;
        }

        foreach (self::candidates($relative) as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function absoluteForWrite(string $relative): string
    {
        $relative = self::normalize($relative);
        $path = Storage::disk('local')->path($relative);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $path;
    }

    public static function delete(string $relative): void
    {
        $relative = self::normalize($relative);
        if ($relative === '') {
            return;
        }

        Storage::disk('local')->delete($relative);

        $legacy = storage_path('app/' . $relative);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }

    /** @return list<string> */
    private static function candidates(string $relative): array
    {
        return array_values(array_unique([
            Storage::disk('local')->path($relative),
            storage_path('app/' . $relative),
            storage_path('app/private/' . $relative),
        ]));
    }

    private static function normalize(string $relative): string
    {
        return ltrim(str_replace('\\', '/', $relative), '/');
    }
}
