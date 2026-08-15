<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceSession extends Model
{
    use HasFactory;

    public const STATE_WELCOME = 'WELCOME';
    public const STATE_GUEST_MENU = 'GUEST_MENU';
    public const STATE_PARENT_MENU = 'PARENT_MENU';
    public const STATE_SELECT_CHILD = 'SELECT_CHILD';
    public const STATE_ANSWER = 'ANSWER';
    public const STATE_ENDED = 'ENDED';
    public const STATE_PIN_ENTRY = 'PIN_ENTRY';
    public const STATE_MORE_MENU = 'MORE_MENU';
    public const STATE_AI_LISTEN = 'AI_LISTEN';
    public const STATE_TRANSFER = 'TRANSFER';

    protected $fillable = [
        'call_id',
        'phone_number',
        'to_number',
        'institution_id',
        'locale',
        'state',
        'menu_profile',
        'pin_verified',
        'pin_attempts',
        'parent_id',
        'user_id',
        'student_id',
        'last_digit',
        'turns',
        'ai_turns',
        'meta',
        'started_at',
        'ended_at',
        'transferred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'pin_verified' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(StudentParent::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isParentMenu(): bool
    {
        return $this->menu_profile === 'parent';
    }

    public function isEnded(): bool
    {
        return $this->state === self::STATE_ENDED || $this->ended_at !== null;
    }
}
