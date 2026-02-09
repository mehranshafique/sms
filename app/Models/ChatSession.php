<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number', 
        'institution_id', 
        'identifier_input', 
        'user_type', 
        'user_id', 
        'otp', 
        'otp_expires_at', 
        'attempts', 
        'status', 
        'locale', // Added
        'last_interaction_at', 
        'expires_at'
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->morphTo();
    }
}