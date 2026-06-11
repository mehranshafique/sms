<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    protected $fillable = [
        'institution_id', 'transport_vehicle_id', 'name',
        'departure_time', 'zones', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'transport_vehicle_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TransportAssignment::class);
    }
}
