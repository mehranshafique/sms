<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PlatformDatabaseBackup extends Model
{
    protected $fillable = [
        'type',
        'status',
        'disk',
        'path',
        'filename',
        'size_bytes',
        'error_message',
        'triggered_by',
        'completed_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function isDownloadable(): bool
    {
        return $this->status === 'ready' && filled($this->path) && Storage::disk($this->disk)->exists($this->path);
    }

    public function absolutePath(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk($this->disk)->path($this->path);
    }

    public function sizeLabel(): string
    {
        $bytes = (int) ($this->size_bytes ?? 0);
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
