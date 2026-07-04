<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'institution_id',
        'chatbot_keyword_id',
        'identifier_input',
        'user_type',
        'user_id',
        'otp',
        'otp_expires_at',
        'attempts',
        'status',
        'menu_profile',
        'participant_type',
        'locale',
        'last_interaction_at',
        'expires_at',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function chatbotKeyword(): BelongsTo
    {
        return $this->belongsTo(ChatbotKeyword::class);
    }

    public function user()
    {
        return $this->morphTo();
    }
}
