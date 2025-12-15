<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'country',
        'city',
        'address',
        'phone',
        'is_active',
    ];

    public function admins()
    {
        return $this->hasMany(User::class, 'institute_id'); // Assuming users table has institute_id
    }

    /**
     * Get all staff members for the institute.
     */
    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get all students for the institute.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
