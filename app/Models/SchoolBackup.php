<?php

namespace App\Models;

use App\Services\Backup\SchoolBackupPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SchoolBackup extends Model
{
    protected $fillable = [
        'institution_id',
        'uuid',
        'type',
        'status',
        'disk_path',
        'file_size',
        'checksum',
        'drive_file_id',
        'triggered_by',
        'include_files',
        'error_message',
        'summary',
    ];

    protected $casts = [
        'include_files' => 'boolean',
        'summary' => 'array',
        'file_size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $backup) {
            if (!$backup->uuid) {
                $backup->uuid = (string) Str::uuid();
            }
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function isDownloadable(): bool
    {
        return $this->status === 'completed' && $this->absolutePath() !== null;
    }

    public function absolutePath(): ?string
    {
        if (!$this->disk_path) {
            return null;
        }

        return SchoolBackupPath::absolute($this->disk_path);
    }
}
