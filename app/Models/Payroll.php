<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'staff_id',
        'month_year',
        'total_days',
        'present_days',
        'absent_days',
        'late_days',
        'basic_pay',
        'total_allowance',
        'total_deduction',
        'net_salary',
        'status',
        'paid_at'
    ];

    protected $casts = [
        'month_year' => 'date',
        'paid_at' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}