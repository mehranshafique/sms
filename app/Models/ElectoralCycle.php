<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ElectoralCycle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'academic_session_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('institution', function (Builder $builder) {
            if (Auth::check() && Auth::user()->institute_id) {
                $builder->where('institution_id', Auth::user()->institute_id);
            }
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class)->orderBy('start_date');
    }

    public function statusLabel(): string
    {
        return __('voting.cycle_status_'.$this->status);
    }
}
