<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name', 'price', 'duration_days', 'modules', 'student_limit', 'staff_limit', 'is_active'
    ];

    protected $casts = [
        'modules' => 'array',
        'is_active' => 'boolean'
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}