<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'institution_id',
        'user_id',
        'assigned_to',
        'subject',
        'category',
        'priority',
        'status',
        'last_reply_at',
        'last_reply_by',
        'user_last_read_at',
        'support_last_read_at',
        'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'user_last_read_at' => 'datetime',
        'support_last_read_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUSES = ['open', 'pending', 'answered', 'resolved', 'closed'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const CATEGORIES = ['general', 'technical', 'billing', 'account', 'feature', 'bug', 'other'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public function lastReplyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reply_by');
    }

    /** Whether the requester has unseen support replies. */
    public function hasUnreadForUser(): bool
    {
        if (!$this->last_reply_at || (int) $this->last_reply_by === (int) $this->user_id) {
            return false;
        }
        return !$this->user_last_read_at || $this->user_last_read_at->lt($this->last_reply_at);
    }

    /** Whether the support team has unseen requester replies. */
    public function hasUnreadForSupport(): bool
    {
        if (!$this->last_reply_at || (int) $this->last_reply_by !== (int) $this->user_id) {
            return false;
        }
        return !$this->support_last_read_at || $this->support_last_read_at->lt($this->last_reply_at);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }
}
