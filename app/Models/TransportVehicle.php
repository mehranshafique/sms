<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportVehicle extends Model
{
    protected $fillable = [
        'institution_id', 'plate_number', 'capacity',
        'driver_name', 'driver_phone', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class);
    }
}
