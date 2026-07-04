<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class ChatbotKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'keyword',
        'language',
        'menu_profile',
        'welcome_message',
    ];

    public function allowedRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'chatbot_keyword_allowed_roles',
            'chatbot_keyword_id',
            'role_id'
        );
    }
}
