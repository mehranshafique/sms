<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $table = 'institutions';

    protected $fillable = [
        'name',
        'code',
        'type',
        'country',
        'city',
        'address',
        'phone',
        'email',
        'logo', // Added logo to fillable
        'is_active',
    ];

    /**
     * Relationship: An Institution has many Campuses.
     */
    public function campuses()
    {
        return $this->hasMany(Campus::class);
    }

    /**
     * Relationship: An Institution has many Academic Sessions.
     */
    public function academicSessions()
    {
        return $this->hasMany(AcademicSession::class);
    }
    
    public function admins()
    {
        return $this->hasMany(User::class, 'institute_id');
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}