<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    protected $table = 'campuses';

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'is_active',
    ];

    /**
     * Relationship: A Campus belongs to an Institution.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Scope: Active campuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}