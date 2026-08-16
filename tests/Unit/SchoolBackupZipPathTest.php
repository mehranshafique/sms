<?php

use App\Services\Backup\SchoolBackupImporter;
use App\Services\Backup\SchoolBackupPath;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

it('opens an uploaded backup zip from the local disk rather than storage/app', function () {
    $relative = 'school-backups/uploads/unit-preview.zip';
    $absolute = SchoolBackupPath::absoluteForWrite($relative);

    $zip = new ZipArchive();
    expect($zip->open($absolute, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();
    $zip->addFromString('digitex-backup.json', json_encode([
        'format' => 'digitex-school-backup',
        'schema_version' => 1,
        'institution' => ['id' => 1, 'code' => 'UNIT', 'name' => 'Unit School'],
        'tables' => ['students' => 1],
    ]));
    $zip->close();

    $legacyPath = storage_path('app/' . $relative);
    $localPath = Storage::disk('local')->path($relative);

    expect(is_file($localPath))->toBeTrue();
    expect(SchoolBackupPath::absolute($relative))->toBe($localPath);

    if ($legacyPath !== $localPath) {
        expect(is_file($legacyPath))->toBeFalse();
    }

    $preview = app(SchoolBackupImporter::class)->preview($localPath);
    expect($preview['manifest']['format'])->toBe('digitex-school-backup')
        ->and($preview['counts']['students'])->toBe(1);

    SchoolBackupPath::delete($relative);
});
