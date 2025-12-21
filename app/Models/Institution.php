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
        'logo', 
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
    
    /**
     * Relationship: Get all users directly linked to this institution (One-to-Many).
     * This fixes the "missing relationships Institution has many user" error.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relationship: Get admins (users) linked to this institution.
     */
    public function admins()
    {
        return $this->hasMany(User::class, 'institute_id');
    }

    /**
     * Relationship: Get Head Officers assigned to this institution (Many-to-Many).
     * Required for the Head Officer module to work correctly.
     */
    public function headOfficers()
    {
        return $this->belongsToMany(User::class, 'institution_head_officers', 'institution_id', 'user_id')
                    ->withTimestamps();
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Relationship: An Institution has many Streams (Options).
     * Added for Option/Stream Module.
     */
    // public function streams()
    // {
    //     return $this->hasMany(Stream::class);
    // }
}