<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Election extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_RESULTS_PUBLISHED = 'results_published';

    public const ELIGIBILITY_ALL = 'all_students';
    public const ELIGIBILITY_GRADES = 'grade_levels';
    public const ELIGIBILITY_SECTIONS = 'class_sections';
    public const ELIGIBILITY_DEPARTMENTS = 'departments';
    public const ELIGIBILITY_MANUAL = 'manual_list';

    protected $fillable = [
        'institution_id',
        'electoral_cycle_id',
        'academic_session_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'eligibility_type',
        'eligibility_ids',
        'max_choices',
        'results_visible_to_voters',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'eligibility_ids' => 'array',
        'results_visible_to_voters' => 'boolean',
        'max_choices' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('institution', function (Builder $builder) {
            if (Auth::check() && Auth::user()->institute_id) {
                $builder->where('institution_id', Auth::user()->institute_id);
            }
        });
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ElectoralCycle::class, 'electoral_cycle_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(ElectionPosition::class)->orderBy('sequence');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function voters(): HasMany
    {
        return $this->hasMany(ElectionVoter::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(ElectionParticipation::class);
    }

    public function ballots(): HasMany
    {
        return $this->hasMany(Ballot::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function isOpenForVoting(): bool
    {
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    public function statusLabel(): string
    {
        return __('voting.status_'.$this->status);
    }

    public function eligibilityLabel(): string
    {
        return __('voting.eligibility_'.$this->eligibility_type);
    }
}
