<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'password',
        // 'institute_id', // Removed from fillable for Head Officers as they use the relationship
        'user_type',
        'phone',
        'address',
        'is_active',
        'language'
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
     * Uses pivot table 'institution_head_officers' created in the migration above.
     */
    public function institutes()
    {
        return $this->belongsToMany(
            Institute::class, 
            'institution_head_officers', 
            'user_id', 
            'institution_id'
        )->withTimestamps();
    }

    /**
     * For Regular Staff/Students: Direct link to ONE institute (if applicable).
     * Usually, this data is better accessed via the Staff or Student profile models,
     * but if a direct column exists on users, this remains valid.
     */
    public function institute()
    {
        return $this->belongsTo(Institute::class);
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