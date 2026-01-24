<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'head_of_department_id'
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function headOfDepartment()
    {
        return $this->belongsTo(Staff::class, 'head_of_department_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}