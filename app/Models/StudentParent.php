<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentParent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parents'; // Explicit table name to avoid confusion

    protected $fillable = [
        'institution_id',
        'user_id',
        'father_name',
        'father_phone',
        'father_occupation',
        'mother_name',
        'mother_phone',
        'mother_occupation',
        'guardian_name',
        'guardian_relation',
        'guardian_phone',
        'guardian_email',
        'family_address',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}