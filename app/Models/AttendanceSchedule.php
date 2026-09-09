<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'check_in_time',
        'check_out_time',
        'late_margin_minutes',
        'is_active',
    ];

    protected $casts = [
        'late_margin_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AttendanceScheduleAssignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Normalize stored time to H:i for display / comparison.
     */
    public function checkInTimeLabel(): string
    {
        return $this->formatTimeValue($this->check_in_time);
    }

    public function checkOutTimeLabel(): ?string
    {
        if ($this->check_out_time === null || $this->check_out_time === '') {
            return null;
        }

        return $this->formatTimeValue($this->check_out_time);
    }

    private function formatTimeValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        $raw = trim((string) $value);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $raw, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return $raw;
    }
}
