<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotKeyword extends Model
{
    use HasFactory;
    
    protected $fillable = ['institution_id', 'keyword', 'language', 'welcome_message'];
}