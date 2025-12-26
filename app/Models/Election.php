<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Election extends Model
{
    use HasFactory, SoftDeletes;

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

    protected static function booted()
    {
        static::addGlobalScope('institution', function (Builder $builder) {
            if (Auth::check() && Auth::user()->institute_id) {
                $builder->where('institution_id', Auth::user()->institute_id);
            }
        });
    }

    public function positions()
    {
        return $this->hasMany(ElectionPosition::class)->orderBy('sequence');
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}