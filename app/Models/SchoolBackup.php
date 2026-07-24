<?php

namespace App\Models;

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
        return $this->status === 'completed' && $this->disk_path && is_file(storage_path('app/' . ltrim($this->disk_path, '/')));
    }
}
