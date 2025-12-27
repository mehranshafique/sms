<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['name', 'iso2', 'phonecode', 'is_active'];

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}