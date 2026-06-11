<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityReply extends Model
{
    protected $fillable = [
        'community_thread_id', 'user_id', 'body', 'is_solution',
    ];

    protected $casts = [
        'is_solution' => 'boolean',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommunityThread::class, 'community_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
