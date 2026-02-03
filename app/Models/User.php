<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Institution; // Fixed: Import Institution Model

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',   // <--- Add this
        'shortcode',
        'password',
        'institute_id', // Useful for non-head officer users
        'user_type',
        'phone',
        'address',
        'is_active',
        'language',
        'profile_picture' // Fixed: Added profile_picture to allowed mass assignment fields
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * For Head Officers: Manage MULTIPLE Institutes.
     * Uses pivot table 'institution_head_officers'.
     */
    public function institutes()
    {
        return $this->belongsToMany(
            Institution::class, // Fixed: Changed from Institute to Institution
            'institution_head_officers', 
            'user_id', 
            'institution_id'
        )->withTimestamps();
    }

    /**
     * For Regular Staff/Students: Direct link to ONE institute.
     */
    public function institute()
    {
        return $this->belongsTo(Institution::class); // Fixed: Changed from Institute to Institution
    }

    /**
     * Get the staff profile associated with the user.
     */
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get the student profile associated with the user.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }
}