<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'staff_id',
        'base_salary',
        'hourly_rate',
        'allowances',
        'deductions',
        'payment_basis'
    ];

    protected $casts = [
        'allowances' => 'array',
        'deductions' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}