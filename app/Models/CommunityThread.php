<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityThread extends Model
{
    protected $fillable = [
        'user_id', 'institution_id', 'category', 'title', 'body',
        'views', 'is_pinned', 'is_locked', 'status',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommunityReply::class)->orderBy('created_at');
    }

    public function repliesCount(): int
    {
        return $this->replies()->count();
    }
}
