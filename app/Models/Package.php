<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name', 'price', 'duration_days', 'modules', 'student_limit', 'staff_limit', 'is_active',
        'ai_enabled', 'ai_unlimited', 'ai_monthly_limit',
    ];

    protected $casts = [
        'modules' => 'array',
        'is_active' => 'boolean',
        'ai_enabled' => 'boolean',
        'ai_unlimited' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}