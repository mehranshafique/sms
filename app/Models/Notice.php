<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Notice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'title',
        'content',
        'type', // info, warning, urgent
        'audience', // all, staff, student, parent
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     * Apply Multi-Tenancy Scope.
     */
    protected static function booted()
    {
        static::addGlobalScope('institution', function (Builder $builder) {
            // If user is logged in and belongs to an institution, filter.
            // If Super Admin (institution_id null) wants to see all, we can skip, 
            // but usually we filter by the active context.
            if (Auth::check() && Auth::user()->institute_id) {
                $builder->where(function($query) {
                    $query->where('institution_id', Auth::user()->institute_id)
                          ->orWhereNull('institution_id'); // Include Global System Notices
                });
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}